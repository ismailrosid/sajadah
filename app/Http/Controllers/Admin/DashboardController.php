<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\PrayerType;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $timezone = $request->user()->getTimezone();
        $today = Carbon::today($timezone);
        $todayStr = $today->toDateString();

        // 1. Key Metrics across the entire system
        $totalUsers = User::count();
        $totalApprovers = User::whereHas('assignedApprovalProcesses', function ($q) {
            $q->where('is_active', true);
        })->count();

        $todayAttendances = Attendance::whereDate('attendance_date', $todayStr)->get();

        $todayValidCount = $todayAttendances->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::Approved])->count();
        $todayPresentCount = $todayAttendances->where('status', AttendanceStatus::Present)->count();
        $todayLateCount = $todayAttendances->where('status', AttendanceStatus::Late)->count();
        $todayRejectedCount = $todayAttendances->where('status', AttendanceStatus::Rejected)->count();
        $todayPendingCount = $todayAttendances->whereIn('status', [AttendanceStatus::PendingApproval, AttendanceStatus::Pending])->count();

        $onTimePercentage = $todayValidCount > 0 ? round(($todayPresentCount / $todayValidCount) * 100) : 100;

        // 2. Pending Attendance Requests (Requiring Approval)
        $pendingRequests = Attendance::with('user')
            ->whereIn('status', [AttendanceStatus::PendingApproval->value, AttendanceStatus::Pending->value])
            ->latest('server_timestamp')
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'user_name' => $a->user?->name ?? 'User',
                'user_email' => $a->user?->email,
                'prayer_label' => $a->prayer_type->label(),
                'attendance_date' => $a->attendance_date->format('d M Y'),
                'time' => $a->server_timestamp->format('H:i'),
                'distance_meters' => $a->distance_meters,
                'request_notes' => $a->request_notes,
                'has_photo' => !empty($a->photo_path),
                'photo_url' => !empty($a->photo_path) ? route('member.attendance.photo', $a->id) : null,
            ]);

        // 3. Attendance per Prayer today
        $prayerBreakdown = [];
        foreach (PrayerType::ordered() as $prayer) {
            $count = $todayAttendances->where('prayer_type', $prayer)->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::Approved])->count();
            $prayerBreakdown[] = [
                'type' => $prayer->value,
                'label' => $prayer->label(),
                'count' => $count,
                'percentage' => $totalUsers > 0 ? round(($count / $totalUsers) * 100) : 0,
            ];
        }

        // 4. Recent Live Attendance Stream
        $recentAttendances = Attendance::with('user')
            ->latest('server_timestamp')
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'user_name' => $a->user?->name ?? 'User',
                'user_avatar' => $a->user?->avatar_path,
                'prayer_label' => $a->prayer_type->label(),
                'time' => $a->server_timestamp->format('H:i'),
                'date' => $a->attendance_date->format('d M Y'),
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->color(),
                'distance_meters' => $a->distance_meters,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'total_users' => $totalUsers,
                'total_approvers' => $totalApprovers,
                'today_total' => $todayAttendances->count(),
                'today_present' => $todayPresentCount,
                'today_late' => $todayLateCount,
                'today_pending' => $todayPendingCount,
                'today_rejected' => $todayRejectedCount,
                'ontime_percentage' => $onTimePercentage,
            ],
            'pendingRequests' => $pendingRequests,
            'prayerBreakdown' => $prayerBreakdown,
            'recentAttendances' => $recentAttendances,
        ]);
    }
}
