<?php

namespace App\Http\Controllers\Member;

use App\Enums\AttendanceStatus;
use App\Enums\PrayerType;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\Attendance\AttendanceValidationService;
use App\Services\Prayer\PrayerScheduleCalculationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceValidationService $attendanceService,
        protected PrayerScheduleCalculationService $prayerService
    ) {}

    public function create(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Admin does not have attendance duties; focus strictly on data management
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $timezone = $user->getTimezone();
        $serverNow = Carbon::now($timezone);
        $todayDate = $serverNow->toDateString();

        // 1. Obtain verified personal prayer schedule
        $durationMinutes = $user->getAttendanceDurationMinutes();
        $schedule = $this->prayerService->getScheduleForUser($user, $todayDate);
        $windowEvaluation = null;
        if ($schedule) {
            $windowEvaluation = $this->prayerService->evaluateWindows(
                $schedule,
                $durationMinutes,
                $serverNow,
                $timezone
            );
        }

        // 2. Get user's attendance status today
        $todayAttendances = Attendance::with('approvals')
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $todayDate)
            ->get()
            ->keyBy(fn($a) => $a->prayer_type->value);

        $prayersData = [];
        foreach (PrayerType::ordered() as $type) {
            $att = $todayAttendances->get($type->value);
            $win = $windowEvaluation['prayers'][$type->value] ?? null;

            $isActuallyAttended = $att && $att->status !== AttendanceStatus::Absent;
            $canLate = !$isActuallyAttended && ($win['is_closed'] ?? false);
            $isMissed = false;

            $prayersData[$type->value] = [
                'type' => $type->value,
                'label' => $type->label(),
                'time' => $win ? $win['time'] : ($schedule ? substr($schedule->getTimeFor($type->value) ?? '--:--', 0, 5) : '--:--'),
                'window_start' => $win['window_start'] ?? null,
                'window_end' => $win['window_end'] ?? null,
                'is_started' => $win['is_started'] ?? false,
                'is_open' => $win['is_open'] ?? false,
                'is_closed' => $win['is_closed'] ?? false,
                'can_late' => $canLate,
                'is_missed' => false,
                'window_status' => $win['status'] ?? 'upcoming',
                'remaining_window_seconds' => $win['remaining_window_seconds'] ?? 0,
                'remaining_until_prayer_seconds' => $win['remaining_until_prayer_seconds'] ?? 0,
                'attended' => $isActuallyAttended,
                'status' => $att?->status->value,
                'status_label' => $att?->status->label(),
                'status_color' => $att?->status->color(),
                'is_approver_verified' => $att ? $att->isApprovedByApprover() : false,
                'is_rejected' => $att ? $att->isRejectedByApprover() : false,
                'time_recorded' => $isActuallyAttended && $att->server_timestamp ? $att->server_timestamp->format('H:i') : null,
                'distance' => $att?->distance_meters,
                'request_notes' => $att?->request_notes,
            ];
        }

        $isTestMode = filter_var(env('ATTENDANCE_TEST_MODE', false), FILTER_VALIDATE_BOOLEAN);

        return Inertia::render('Member/Attendance/Create', [
            'prayers' => $prayersData,
            'isTestMode' => $isTestMode,
            'setting' => [
                'attendance_duration_minutes' => $durationMinutes,
                'require_selfie' => true,
                'require_gps' => true,
                'city' => $user->getCity(),
            ],
            'activeOpenWindow' => $windowEvaluation['active_open_window'] ?? null,
            'nextUpcomingPrayer' => $windowEvaluation['next_upcoming_prayer'] ?? null,
            'serverTime' => $serverNow->toIso8601String(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isTestMode = filter_var(env('ATTENDANCE_TEST_MODE', false), FILTER_VALIDATE_BOOLEAN);

        $validated = $request->validate([
            'prayer_type' => ['required', 'string', 'in:subuh,dzuhur,ashar,maghrib,isya'],
            'latitude' => [$isTestMode ? 'nullable' : 'required', 'numeric', 'between:-90,90'],
            'longitude' => [$isTestMode ? 'nullable' : 'required', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['nullable', 'numeric'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_late_request' => ['nullable', 'boolean'],
            'request_notes' => [$isTestMode ? 'nullable' : 'required_if:is_late_request,true', 'nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // Max 5MB
        ]);

        if (empty($validated['latitude'])) {
            $validated['latitude'] = $user->getLatitude() ?? -6.20880000;
        }
        if (empty($validated['longitude'])) {
            $validated['longitude'] = $user->getLongitude() ?? 106.84560000;
        }

        $attendance = $this->attendanceService->process(
            $user,
            $validated,
            $request->file('photo')
        );

        if ($attendance->status === AttendanceStatus::Pending || $attendance->status === AttendanceStatus::PendingApproval) {
            $msg = $attendance->request_notes
                ? "Pengajuan susulan Sholat {$attendance->prayer_type->label()} berhasil dikirim."
                : "Absensi Sholat {$attendance->prayer_type->label()} berhasil dikirim.";

            return redirect()->route('member.attendance.create')
                ->with('info', $msg)
                ->with('success', $msg);
        }

        if ($attendance->status === AttendanceStatus::Late) {
            $msg = "Absensi susulan Sholat {$attendance->prayer_type->label()} berhasil dicatat.";
            return redirect()->route('member.attendance.create')
                ->with('info', $msg)
                ->with('success', $msg);
        }

        if ($attendance->status === AttendanceStatus::Rejected) {
            return redirect()->route('member.attendance.create')
                ->with('error', "Absensi ditolak: {$attendance->rejection_reason}.");
        }

        $successMsg = "Absensi Sholat {$attendance->prayer_type->label()} berhasil dicatat.";
        return redirect()->route('member.attendance.create')
            ->with('info', $successMsg)
            ->with('success', $successMsg);
    }

    public function photo(Request $request, Attendance $attendance): BinaryFileResponse
    {
        $user = $request->user();

        // Security check: Attendee, Admin, or an Approver can view
        if ($attendance->user_id !== $user->id && !$user->isAdmin() && !$user->isApprover()) {
            abort(403, 'Akses foto ditolak.');
        }

        if (!$attendance->photo_path || !Storage::disk('local')->exists($attendance->photo_path)) {
            abort(404, 'File foto tidak ditemukan.');
        }

        return response()->file(Storage::disk('local')->path($attendance->photo_path));
    }
}
