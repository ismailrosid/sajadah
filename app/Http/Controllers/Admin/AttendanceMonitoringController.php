<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\PrayerType;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceMonitoringController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $prayerType = $request->query('prayer_type');
        $status = $request->query('status');
        $date = $request->query('date', Carbon::today()->toDateString());

        $query = Attendance::with(['user', 'approver'])
            ->latest('server_timestamp');

        if ($date) {
            $query->whereDate('attendance_date', $date);
        }

        if ($prayerType && in_array($prayerType, ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'])) {
            $query->where('prayer_type', $prayerType);
        }

        if ($status) {
            if ($status === 'pending') {
                $query->whereIn('status', ['pending', 'pending_approval']);
            } elseif ($status === 'approved') {
                $query->whereIn('status', ['approved', 'present', 'late']);
            } elseif ($status === 'rejected') {
                $query->where('status', 'rejected');
            }
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $attendances = $query->with(['user', 'approver', 'approvals'])
            ->paginate(20)
            ->withQueryString()
            ->through(fn($a) => [
                'id' => $a->id,
                'uuid' => $a->uuid,
                'user_name' => $a->user?->name ?? 'User',
                'user_email' => $a->user?->email,
                'user_avatar' => $a->user?->avatar_path,
                'prayer_type' => $a->prayer_type->value,
                'prayer_label' => $a->prayer_type->label(),
                'attendance_date' => $a->attendance_date->format('d M Y'),
                'time' => $a->server_timestamp->format('H:i:s'),
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->color(),
                'is_approver_verified' => $a->isApprovedByApprover(),
                'is_rejected' => $a->isRejectedByApprover(),
                'has_photo' => !empty($a->photo_path),
                'photo_url' => !empty($a->photo_path) ? route('member.attendance.photo', $a->id) : null,
                'latitude' => $a->latitude,
                'longitude' => $a->longitude,
                'distance_meters' => $a->distance_meters,
                'approval_source' => $a->approval_source,
                'approved_by_name' => $a->approver?->name,
                'request_notes' => $a->request_notes,
                'rejection_reason' => $a->rejection_reason,
            ]);

        $stats = [
            'total' => Attendance::whereDate('attendance_date', $date)->count(),
            'present' => Attendance::whereDate('attendance_date', $date)->whereIn('status', ['present', 'approved'])->count(),
            'late' => Attendance::whereDate('attendance_date', $date)->where('status', 'late')->count(),
            'pending' => Attendance::whereDate('attendance_date', $date)->whereIn('status', ['pending', 'pending_approval'])->count(),
            'rejected' => Attendance::whereDate('attendance_date', $date)->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/Attendances/Index', [
            'attendances' => $attendances,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'prayer_type' => $prayerType,
                'status' => $status,
                'date' => $date,
            ],
        ]);
    }
}
