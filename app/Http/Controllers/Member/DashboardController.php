<?php

namespace App\Http\Controllers\Member;

use App\Enums\AttendanceStatus;
use App\Enums\PrayerType;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\PrayerSchedule;
use App\Services\Prayer\PrayerScheduleCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected PrayerScheduleCalculationService $prayerService
    ) {}

    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Admin strictly focuses on data management, redirect to admin dashboard
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $timezone = $user->getTimezone();
        $serverNow = Carbon::now($timezone);
        $todayStr = $serverNow->toDateString();

        // 1. Get today's personal prayer schedule and evaluate automatic windows
        $durationMinutes = $user->getAttendanceDurationMinutes();
        $schedule = $this->prayerService->getScheduleForUser($user, $todayStr);
        $windowEvaluation = null;

        if ($schedule) {
            $windowEvaluation = $this->prayerService->evaluateWindows(
                $schedule,
                $durationMinutes,
                $serverNow,
                $timezone
            );
        }

        // 2. Get today's personal attendances for user
        $todayAttendances = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $todayStr)
            ->get()
            ->keyBy(fn($a) => $a->prayer_type->value);

        $prayers = [];
        $userCreatedAt = $user->created_at ? $user->created_at->copy()->timezone($timezone) : null;
        $nextPrayerMap = [
            'subuh' => 'dzuhur',
            'dzuhur' => 'ashar',
            'ashar' => 'maghrib',
            'maghrib' => 'isya',
            'isya' => 'subuh',
        ];

        foreach (PrayerType::ordered() as $type) {
            $win = $windowEvaluation['prayers'][$type->value] ?? null;
            $att = $todayAttendances->get($type->value);

            $isActuallyAttended = $att && $att->status !== AttendanceStatus::Absent;
            $isMissed = ($win['is_missed'] ?? false) || ($att && $att->status === AttendanceStatus::Absent);
            $canLate = $win['can_late'] ?? false;
            $isBeforeAccountCreation = false;

            if ($att && $att->status === AttendanceStatus::Absent) {
                $isMissed = true;
                $canLate = false;
            }

            if ($userCreatedAt && $schedule && (!$att || $att->status === AttendanceStatus::Absent)) {
                $nextKey = $nextPrayerMap[$type->value];
                $nextTimeStr = $schedule->getTimeFor($nextKey) ?? '00:00';
                $nextPrayerStart = Carbon::parse("{$todayStr} {$nextTimeStr}", $timezone);
                if ($type->value === 'isya') {
                    $nextPrayerStart->addDay();
                }

                if ($nextPrayerStart->lte($userCreatedAt)) {
                    $isBeforeAccountCreation = true;
                    $isMissed = false;
                    $canLate = false;
                }
            }

            $prayers[] = [
                'type' => $type->value,
                'label' => $type->label(),
                'time' => $win ? $win['time'] : ($schedule ? substr($schedule->getTimeFor($type->value) ?? '00:00', 0, 5) : '00:00'),
                'window_start' => $win['window_start'] ?? null,
                'window_end' => $win['window_end'] ?? null,
                'is_started' => $win['is_started'] ?? false,
                'is_open' => $win['is_open'] ?? false,
                'is_closed' => $win['is_closed'] ?? false,
                'can_late' => $isActuallyAttended ? false : $canLate,
                'is_missed' => $isActuallyAttended ? false : $isMissed,
                'is_before_registration' => $isBeforeAccountCreation,
                'window_status' => $win['status'] ?? 'upcoming',
                'remaining_window_seconds' => $win['remaining_window_seconds'] ?? 0,
                'remaining_until_prayer_seconds' => $win['remaining_until_prayer_seconds'] ?? 0,
                'attended' => $isActuallyAttended,
                'status' => $att?->status->value,
                'status_label' => $att?->status->label(),
                'status_color' => $att?->status->color(),
                'recorded_at' => $isActuallyAttended && $att->server_timestamp ? $att->server_timestamp->format('H:i') : null,
                'distance' => $att?->distance_meters,
            ];
        }

        // Active open window for closing countdown banner
        $activeOpenWindow = $windowEvaluation['active_open_window'] ?? null;
        $nextPrayer = $windowEvaluation['next_upcoming_prayer'] ?? null;

        // If all prayers passed today, next prayer is Subuh tomorrow
        if (!$nextPrayer && $schedule) {
            $tomorrowSubuh = Carbon::tomorrow($timezone)->setTimeFromTimeString($schedule->fajr);
            $nextPrayer = [
                'type' => 'subuh',
                'label' => 'Subuh',
                'time' => substr($schedule->fajr, 0, 5),
                'window_start_iso' => $tomorrowSubuh->toIso8601String(),
                'datetime' => $tomorrowSubuh->toIso8601String(),
            ];
        } elseif ($nextPrayer && !isset($nextPrayer['datetime'])) {
            $nextPrayer['datetime'] = $nextPrayer['window_start_iso'];
        }

        // 3. Weekly Statistics (Past 7 days)
        $sevenDaysAgo = $serverNow->copy()->subDays(6)->toDateString();
        $weeklyAttendances = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', '>=', $sevenDaysAgo)
            ->whereIn('status', [AttendanceStatus::Present->value, AttendanceStatus::Late->value])
            ->get();

        $totalPossibleWeekly = 7 * 5;
        $weeklyAttendedCount = $weeklyAttendances->count();
        $weeklyPercentage = $totalPossibleWeekly > 0 ? round(($weeklyAttendedCount / $totalPossibleWeekly) * 100) : 0;

        $dailyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $serverNow->copy()->subDays($i);
            $count = $weeklyAttendances->where('attendance_date', $d->toDateString())->count();
            $dailyTrend[] = [
                'date' => $d->format('D, d M'),
                'short' => $d->isoFormat('dd'),
                'count' => $count,
            ];
        }

        // 4. Streak Counter
        $streak = 0;
        $checkDate = $serverNow->copy();
        while (true) {
            $hadAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('attendance_date', $checkDate->toDateString())
                ->whereIn('status', [AttendanceStatus::Present->value, AttendanceStatus::Late->value])
                ->exists();

            if ($hadAttendance) {
                $streak++;
                $checkDate->subDay();
            } else {
                if ($checkDate->isToday()) {
                    $checkDate->subDay();
                    continue;
                }
                break;
            }
        }

        return Inertia::render('Member/Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'city' => $user->getCity(),
                'timezone' => $timezone,
            ],
            'todayAttendancesCount' => $todayAttendances->whereNotIn('status', [AttendanceStatus::Rejected, AttendanceStatus::Absent])->count(),
            'prayers' => $prayers,
            'nextPrayer' => $nextPrayer,
            'activeOpenWindow' => $activeOpenWindow,
            'durationMinutes' => $durationMinutes,
            'weeklyStats' => [
                'attended' => $weeklyAttendedCount,
                'target' => $totalPossibleWeekly,
                'percentage' => $weeklyPercentage,
                'trend' => $dailyTrend,
            ],
            'streak' => $streak,
            'serverTime' => $serverNow->toIso8601String(),
        ]);
    }
}
