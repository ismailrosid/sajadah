<?php

namespace App\Http\Controllers\Member;

use App\Enums\AttendanceStatus;
use App\Enums\PrayerType;
use App\Http\Controllers\Controller;
use App\Models\ApprovalProcess;
use App\Models\Attendance;
use App\Models\User;
use App\Services\Prayer\PrayerScheduleCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $viewer = $request->user();

        // Cari anakan (subordinates) yang di-approve oleh user ini
        $assignedProcesses = ApprovalProcess::where('approver_id', $viewer->id)
            ->where('is_active', true)
            ->with('user')
            ->get();

        $subordinates = $assignedProcesses
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]);

        // Admin monitors all user attendances rather than personal history unless viewing subordinate/personal
        if ($viewer->isAdmin() && !$request->has('user_id') && !$request->has('personal') && $subordinates->isEmpty()) {
            return redirect()->route('admin.attendances.index');
        }

        // Tentukan target user yang sedang dilihat
        $targetUser = $viewer;
        $requestedUserId = $request->query('user_id');

        if ($requestedUserId && $requestedUserId != $viewer->id) {
            $isAuthorized = $viewer->isAdmin() || $subordinates->contains('id', (int) $requestedUserId);
            if ($isAuthorized) {
                $targetUser = User::find($requestedUserId) ?? $viewer;
            }
        }

        $month = $request->query('month', now()->format('Y-m'));
        $prayerType = $request->query('prayer_type');
        $status = $request->query('status');

        $startDate = Carbon::parse("{$month}-01")->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Otomatis sinkronisasi sholat yang terlewat (Tidak Sholat) untuk user yang sedang dilihat
        $this->syncMissedAttendances($targetUser, $startDate, $endDate);

        $query = Attendance::where('user_id', $targetUser->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($prayerType && in_array($prayerType, ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'])) {
            $query->where('prayer_type', $prayerType);
        }

        if ($status) {
            if ($status === 'present') {
                $query->whereIn('status', [AttendanceStatus::Present->value, AttendanceStatus::Approved->value]);
            } elseif (in_array($status, ['late', 'rejected', 'absent', 'pending', 'pending_approval', 'approved'])) {
                $query->where('status', $status);
            }
        }

        $attendances = $query->orderByDesc('attendance_date')
            ->orderByDesc('server_timestamp')
            ->paginate(15)
            ->withQueryString()
            ->through(fn($a) => [
                'id' => $a->id,
                'uuid' => $a->uuid,
                'prayer_type' => $a->prayer_type->value,
                'prayer_label' => $a->prayer_type->label(),
                'attendance_date' => $a->attendance_date->format('d M Y'),
                'attendance_date_raw' => $a->attendance_date->toDateString(),
                'time' => $a->server_timestamp->format('H:i'),
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->color(),
                'distance_meters' => $a->distance_meters,
                'gps_accuracy' => $a->gps_accuracy,
                'latitude' => $a->latitude,
                'longitude' => $a->longitude,
                'location_name' => $a->metadata['location_name'] ?? null,
                'address' => $a->metadata['address'] ?? null,
                'has_photo' => !empty($a->photo_path),
                'photo_url' => !empty($a->photo_path) ? route('member.attendance.photo', $a->id) : null,
                'rejection_reason' => $a->rejection_reason,
                'request_notes' => $a->request_notes,
            ]);

        // Monthly stats breakdown
        $monthlyPresent = Attendance::where('user_id', $targetUser->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('status', [AttendanceStatus::Present->value, AttendanceStatus::Approved->value])
            ->count();

        $monthlyLate = Attendance::where('user_id', $targetUser->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', AttendanceStatus::Late->value)
            ->count();

        $monthlyAbsent = Attendance::where('user_id', $targetUser->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', AttendanceStatus::Absent->value)
            ->count();

        return Inertia::render('Member/History/Index', [
            'attendances' => $attendances,
            'filters' => [
                'month' => $month,
                'prayer_type' => $prayerType,
                'status' => $status,
                'user_id' => $targetUser->id !== $viewer->id ? (string) $targetUser->id : '',
            ],
            'stats' => [
                'present' => $monthlyPresent,
                'late' => $monthlyLate,
                'absent' => $monthlyAbsent,
                'total' => $monthlyPresent + $monthlyLate,
            ],
            'isViewingSubordinate' => $targetUser->id !== $viewer->id,
            'targetUser' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
            'subordinates' => $subordinates,
        ]);
    }

    /**
     * Otomatis catat status 'Tidak Sholat' (absent) untuk jadwal yang sudah lewat
     * dan tidak pernah dihadiri oleh user.
     * Jadwal sholat yang sudah selesai sebelum user mendaftar/buat akun diabaikan.
     */
    protected function syncMissedAttendances(User $user, Carbon $startDate, Carbon $endDate): void
    {
        $timezone = $user->getTimezone();
        $now = Carbon::now($timezone);
        $userCreatedAt = $user->created_at ? $user->created_at->copy()->timezone($timezone) : null;

        // Bersihkan data absent yang tidak sengaja tercatat untuk jadwal sebelum user membuat akun
        if ($userCreatedAt) {
            Attendance::where('user_id', $user->id)
                ->where('status', AttendanceStatus::Absent->value)
                ->where('server_timestamp', '<', $userCreatedAt->toDateTimeString())
                ->delete();
        }

        $effectiveStart = $startDate->copy()->max($userCreatedAt ? $userCreatedAt->copy()->startOfDay() : $startDate);
        $effectiveEnd = $endDate->copy()->min($now->copy()->endOfDay());

        if ($effectiveStart->gt($effectiveEnd)) {
            return;
        }

        $calcService = app(PrayerScheduleCalculationService::class);
        $prayerKeys = ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'];
        $nextPrayerMap = [
            'subuh' => 'dzuhur',
            'dzuhur' => 'ashar',
            'ashar' => 'maghrib',
            'maghrib' => 'isya',
            'isya' => 'subuh',
        ];

        $cursor = $effectiveStart->copy();

        while ($cursor->lte($effectiveEnd)) {
            $dateStr = $cursor->toDateString();
            $schedule = $calcService->getOrFetchSchedule($user, null, $cursor);

            if ($schedule) {
                $prayerTimes = [
                    'subuh' => $schedule->fajr,
                    'dzuhur' => $schedule->dhuhr,
                    'ashar' => $schedule->asr,
                    'maghrib' => $schedule->maghrib,
                    'isya' => $schedule->isha,
                ];

                foreach ($prayerKeys as $key) {
                    $nextKey = $nextPrayerMap[$key];
                    $nextTime = $prayerTimes[$nextKey];
                    $nextPrayerStart = Carbon::parse("{$dateStr} {$nextTime}", $timezone);
                    if ($key === 'isya') {
                        $nextPrayerStart->addDay();
                    }

                    // Abaikan jika jadwal sholat ini sudah berakhir SEBELUM user mendaftar/buat akun
                    if ($userCreatedAt && $nextPrayerStart->lte($userCreatedAt)) {
                        continue;
                    }

                    $isPassed = false;
                    if ($cursor->isPast() && !$cursor->isToday()) {
                        $isPassed = true;
                    } elseif ($cursor->isToday()) {
                        $isPassed = $now->gte($nextPrayerStart);
                    }

                    if ($isPassed) {
                        $alreadyExists = Attendance::where('user_id', $user->id)
                            ->where('prayer_type', $key)
                            ->whereDate('attendance_date', $dateStr)
                            ->exists();

                        if (!$alreadyExists) {
                            $prayerTime = Carbon::parse("{$dateStr} {$prayerTimes[$key]}", $timezone);
                            try {
                                Attendance::create([
                                    'uuid' => (string) Str::uuid(),
                                    'user_id' => $user->id,
                                    'prayer_type' => $key,
                                    'attendance_date' => $dateStr,
                                    'server_timestamp' => $prayerTime->toDateTimeString(),
                                    'status' => AttendanceStatus::Absent->value,
                                    'risk_score' => 0,
                                ]);
                            } catch (\Throwable) {
                                // Concurrent safe
                            }
                        }
                    }
                }
            }

            $cursor->addDay();
        }
    }
}
