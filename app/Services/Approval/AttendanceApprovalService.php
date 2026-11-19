<?php

namespace App\Services\Approval;

use App\Enums\AttendanceStatus;
use App\Models\ActivityLog;
use App\Models\ApprovalProcess;
use App\Models\ApprovalRole;
use App\Models\Attendance;
use App\Models\AttendanceApproval;
use App\Models\PrayerSchedule;
use App\Models\User;
use App\Notifications\AttendanceApprovedNotification;
use App\Notifications\AttendanceAutoApprovedNotification;
use App\Notifications\AttendanceRejectedNotification;
use App\Notifications\NewApprovalTaskNotification;
use App\Services\Prayer\PrayerScheduleCalculationService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceApprovalService
{
    public function __construct(
        protected PrayerScheduleCalculationService $prayerService
    ) {}

    /**
     * Check if a proposed approver assignment would create a circular approval chain.
     */
    public function wouldCreateCircularApproval(int $userId, int $approverId): bool
    {
        if ($userId === $approverId) {
            return true;
        }

        $visited = [$userId];
        $current = $approverId;

        while ($current) {
            if (in_array($current, $visited, true)) {
                return true;
            }
            $visited[] = $current;

            $nextProcess = ApprovalProcess::where('user_id', $current)
                ->where('is_active', true)
                ->first();

            $current = $nextProcess ? $nextProcess->approver_id : null;
        }

        return false;
    }

    /**
     * Initialize approval workflow records for a freshly recorded attendance.
     */
    public function initializeApproval(Attendance $attendance): void
    {
        // 1. Fetch user's active configured approval processes
        $processes = ApprovalProcess::where('user_id', $attendance->user_id)
            ->where('is_active', true)
            ->orderBy('approval_level', 'asc')
            ->get();

        if ($processes->isNotEmpty()) {
            foreach ($processes as $process) {
                AttendanceApproval::create([
                    'attendance_id' => $attendance->id,
                    'approver_id' => $process->approver_id,
                    'approval_level' => $process->approval_level,
                    'status' => 'pending',
                    'approval_source' => null,
                ]);
            }

            // Update attendance status to pending (preserving PendingApproval if late request)
            $pendingStatus = ($attendance->status === AttendanceStatus::PendingApproval)
                ? AttendanceStatus::PendingApproval
                : AttendanceStatus::Pending;
            $attendance->update([
                'status' => $pendingStatus,
            ]);

            // Notify level 1 approver
            $firstProcess = $processes->first();
            if ($firstProcess && $firstProcess->approver) {
                try {
                    $firstProcess->approver->notify(new NewApprovalTaskNotification($attendance, $firstProcess->approval_level));
                } catch (\Throwable $e) {
                    // Ignore notification delivery failure
                }
            }
        } else {
            // User yang TIDAK memiliki alur approval / approver:
            // Langsung auto-approve (Hadir / Terlambat) dan tidak masuk ke antrean pending approval ataupun admin.
            $isLate = ($attendance->status === AttendanceStatus::Late
                || $attendance->status === AttendanceStatus::PendingApproval
                || !empty($attendance->metadata['is_late_request']));
            $approvedStatus = $isLate ? AttendanceStatus::Late : AttendanceStatus::Present;

            $attendance->update([
                'status' => $approvedStatus,
            ]);

            AttendanceApproval::create([
                'attendance_id' => $attendance->id,
                'approver_id' => null,
                'approval_level' => 1,
                'status' => 'approved',
                'approval_source' => 'auto_approved',
                'note' => 'Disetujui otomatis karena pengguna tidak memiliki atasan/approver.',
                'approved_at' => now(),
            ]);
        }
    }

    /**
     * Check if a given user can approve the specified attendance.
     */
    public function canUserApprove(User $user, Attendance $attendance): bool
    {
        if ($user->id === $attendance->user_id) {
            return false; // Cannot approve own attendance
        }

        if (!$attendance->isPending()) {
            return false; // Already finished
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Get current pending approval
        $currentApproval = $attendance->approvals()
            ->where('status', 'pending')
            ->orderBy('approval_level', 'asc')
            ->first();

        if ($currentApproval && $currentApproval->approver_id === $user->id) {
            return true;
        }

        // If approval has no approver assigned yet (unassigned pending), any approver can approve
        if ($currentApproval && is_null($currentApproval->approver_id) && $user->isApprover()) {
            return true;
        }

        return false;
    }

    /**
     * Approve an attendance (single level or multi-level workflow).
     */
    public function approve(Attendance $attendance, User $approver, ?string $notes = null): AttendanceApproval
    {
        if ($approver->id === $attendance->user_id) {
            throw ValidationException::withMessages([
                'approver' => 'Tidak dapat menyetujui absensi sendiri.',
            ]);
        }

        if (!$attendance->isPending()) {
            throw ValidationException::withMessages([
                'attendance' => 'Absensi sudah selesai diproses.',
            ]);
        }

        if (!$this->canUserApprove($approver, $attendance)) {
            throw ValidationException::withMessages([
                'approver' => 'Akses approval ditolak.',
            ]);
        }

        return DB::transaction(function () use ($attendance, $approver, $notes) {
            // 1. Get current pending approval record
            $currentApproval = $attendance->approvals()
                ->where('status', 'pending')
                ->orderBy('approval_level', 'asc')
                ->first();

            if (!$currentApproval) {
                // Create level 1 approval record if not exists
                $currentApproval = AttendanceApproval::create([
                    'attendance_id' => $attendance->id,
                    'approver_id' => $approver->id,
                    'approval_level' => 1,
                    'status' => 'pending',
                ]);
            }

            // Update current approval level to approved
            $currentApproval->update([
                'approver_id' => $approver->id,
                'status' => 'approved',
                'approval_source' => 'approver',
                'note' => $notes,
                'approved_at' => now(),
            ]);

            // 2. Check if next level approval is pending
            $nextApproval = $attendance->approvals()
                ->where('status', 'pending')
                ->where('approval_level', '>', $currentApproval->approval_level)
                ->orderBy('approval_level', 'asc')
                ->first();

            if ($nextApproval) {
                // Multi-level: Next level approver needs to approve
                if ($nextApproval->approver) {
                    try {
                        $nextApproval->approver->notify(new NewApprovalTaskNotification($attendance, $nextApproval->approval_level));
                    } catch (\Throwable $e) {}
                }
            } else {
                // Final level approved: Update attendance status
                $finalStatus = ($attendance->status === AttendanceStatus::PendingApproval)
                    ? AttendanceStatus::Late
                    : AttendanceStatus::Approved;

                $attendance->update([
                    'status' => $finalStatus,
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                    'approval_notes' => $notes,
                    'approval_source' => 'approver',
                ]);

                // Notify user
                if ($attendance->user) {
                    try {
                        $attendance->user->notify(new AttendanceApprovedNotification($attendance, $approver, $notes));
                    } catch (\Throwable $e) {}
                }
            }

            ActivityLog::create([
                'user_id' => $approver->id,
                'action' => 'approve_attendance',
                'subject_type' => Attendance::class,
                'subject_id' => $attendance->id,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'details' => [
                    'prayer' => $attendance->prayer_type->value,
                    'level' => $currentApproval->approval_level,
                    'source' => 'approver',
                    'notes' => $notes,
                ],
            ]);

            return $currentApproval;
        });
    }

    /**
     * Reject an attendance with required reason.
     */
    public function reject(Attendance $attendance, User $approver, string $reason): AttendanceApproval
    {
        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        if ($approver->id === $attendance->user_id) {
            throw ValidationException::withMessages([
                'approver' => 'Tidak dapat menolak absensi sendiri.',
            ]);
        }

        if (!$attendance->isPending()) {
            throw ValidationException::withMessages([
                'attendance' => 'Absensi sudah selesai diproses.',
            ]);
        }

        if (!$this->canUserApprove($approver, $attendance)) {
            throw ValidationException::withMessages([
                'approver' => 'Akses approval ditolak.',
            ]);
        }

        return DB::transaction(function () use ($attendance, $approver, $reason) {
            $currentApproval = $attendance->approvals()
                ->where('status', 'pending')
                ->orderBy('approval_level', 'asc')
                ->first();

            if (!$currentApproval) {
                $currentApproval = AttendanceApproval::create([
                    'attendance_id' => $attendance->id,
                    'approver_id' => $approver->id,
                    'approval_level' => 1,
                    'status' => 'pending',
                ]);
            }

            $currentApproval->update([
                'approver_id' => $approver->id,
                'status' => 'rejected',
                'approval_source' => 'approver',
                'note' => $reason,
                'rejected_at' => now(),
            ]);

            // Cancel any subsequent levels
            $attendance->approvals()
                ->where('status', 'pending')
                ->where('approval_level', '>', $currentApproval->approval_level)
                ->update([
                    'status' => 'rejected',
                    'note' => 'Dibatalkan karena ditolak pada tingkat sebelumnya.',
                    'rejected_at' => now(),
                ]);

            $attendance->update([
                'status' => AttendanceStatus::Rejected,
                'rejection_reason' => $reason,
                'approval_source' => 'approver',
            ]);

            // Notify user
            if ($attendance->user) {
                try {
                    $attendance->user->notify(new AttendanceRejectedNotification($attendance, $approver, $reason));
                } catch (\Throwable $e) {}
            }

            ActivityLog::create([
                'user_id' => $approver->id,
                'action' => 'reject_attendance',
                'subject_type' => Attendance::class,
                'subject_id' => $attendance->id,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'details' => [
                    'prayer' => $attendance->prayer_type->value,
                    'level' => $currentApproval->approval_level,
                    'source' => 'approver',
                    'reason' => $reason,
                ],
            ]);

            return $currentApproval;
        });
    }

    /**
     * Automatic Approval by System:
     * If the prayer time for that date has passed and attendance is still pending,
     * automatically update status to approved with approval_source = 'system'.
     */
    public function autoApproveExpiredPendingAttendances(): int
    {
        $pendingAttendances = Attendance::with(['user', 'community', 'approvals'])
            ->whereIn('status', [AttendanceStatus::Pending->value, AttendanceStatus::PendingApproval->value])
            ->get();

        $approvedCount = 0;

        foreach ($pendingAttendances as $attendance) {
            $user = $attendance->user;
            if (!$user) continue;

            $timezone = $user->getTimezone();
            $serverNow = Carbon::now($timezone);
            $attendanceDate = $attendance->attendance_date;

            // Check if attendance date is strictly in the past
            $isPastDate = $attendanceDate->copy()->startOfDay()->lt($serverNow->copy()->startOfDay());

            // Or if today, check whether the prayer window has ended
            $isPrayerExpired = false;
            if ($isPastDate) {
                $isPrayerExpired = true;
            } else {
                // Today: calculate schedule and window
                $schedule = $this->prayerService->getScheduleForUser($user, $attendanceDate->toDateString());
                if ($schedule) {
                    $prayerTimeStr = $schedule->getTimeFor($attendance->prayer_type->value);
                    if ($prayerTimeStr) {
                        $durationMinutes = $user->getAttendanceDurationMinutes();
                        $prayerStart = Carbon::parse("{$attendanceDate->toDateString()} {$prayerTimeStr}", $timezone);
                        $prayerEnd = $prayerStart->copy()->addMinutes($durationMinutes);

                        if ($serverNow->gt($prayerEnd)) {
                            $isPrayerExpired = true;
                        }
                    }
                }
            }

            if ($isPrayerExpired) {
                DB::transaction(function () use ($attendance, &$approvedCount) {
                    // Update all pending approval levels to approved by system
                    $attendance->approvals()
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'approved',
                            'approval_source' => 'system',
                            'approved_at' => now(),
                            'note' => 'Disetujui otomatis oleh sistem karena waktu sholat telah terlewati.',
                        ]);

                    // If no approvals existed, create one
                    if ($attendance->approvals()->count() === 0) {
                        AttendanceApproval::create([
                            'attendance_id' => $attendance->id,
                            'approval_level' => 1,
                            'status' => 'approved',
                            'approval_source' => 'system',
                            'approved_at' => now(),
                            'note' => 'Disetujui otomatis oleh sistem karena waktu sholat telah terlewati.',
                        ]);
                    }

                    $attendance->update([
                        'status' => AttendanceStatus::Approved,
                        'approval_source' => 'system',
                        'approved_at' => now(),
                        'approval_notes' => 'Disetujui otomatis oleh sistem karena waktu sholat telah terlewati.',
                    ]);

                    $approvedCount++;

                    // Notify user
                    if ($attendance->user) {
                        try {
                            $attendance->user->notify(new AttendanceAutoApprovedNotification($attendance));
                        } catch (\Throwable $e) {}
                    }
                });
            }
        }

        return $approvedCount;
    }

    /**
     * Get Grouped Daily Approvals:
     * One row per attendance date!
     */
    public function getGroupedDailyApprovals(User $viewer, array $filters = []): LengthAwarePaginator
    {
        $query = Attendance::query();

        // 1. Authorization Scope:
        // Admin can see all attendances.
        // Approvers can only see attendances of users assigned to them (and never their own attendance).
        if (!$viewer->isAdmin()) {
            $query->where('user_id', '!=', $viewer->id)
                ->where(function ($q) use ($viewer) {
                    $q->whereHas('approvals', function ($sub) use ($viewer) {
                        $sub->where('approver_id', $viewer->id);
                    })
                    ->orWhereHas('user.approvalProcesses', function ($sub) use ($viewer) {
                        $sub->where('approver_id', $viewer->id)->where('is_active', true);
                    });
                });
        }

        // 2. Filters
        if (!empty($filters['start_date'])) {
            $query->whereDate('attendance_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('attendance_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('attendance_date', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Grouping Query by attendance_date
        $groupedQuery = $query->select(
            'attendance_date',
            DB::raw('COUNT(id) as total_attendances'),
            DB::raw("SUM(CASE WHEN status IN ('pending', 'pending_approval') THEN 1 ELSE 0 END) as pending_count"),
            DB::raw("SUM(CASE WHEN status IN ('approved', 'present', 'late') THEN 1 ELSE 0 END) as approved_count"),
            DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count")
        )
        ->groupBy('attendance_date')
        ->orderByDesc('attendance_date');

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'pending') {
                $groupedQuery->having('pending_count', '>', 0);
            } elseif ($filters['status'] === 'approved') {
                $groupedQuery->having('approved_count', '>', 0);
            } elseif ($filters['status'] === 'rejected') {
                $groupedQuery->having('rejected_count', '>', 0);
            }
        }

        $perPage = $filters['per_page'] ?? 10;
        $paginator = $groupedQuery->paginate($perPage)->withQueryString();

        $paginator->through(fn($row) => [
            'date_raw' => Carbon::parse($row->attendance_date)->toDateString(),
            'attendance_date' => Carbon::parse($row->attendance_date)->translatedFormat('d M Y'),
            'attendance_day' => Carbon::parse($row->attendance_date)->translatedFormat('l'),
            'total_attendances' => (int) $row->total_attendances,
            'pending_count' => (int) $row->pending_count,
            'approved_count' => (int) $row->approved_count,
            'rejected_count' => (int) $row->rejected_count,
        ]);

        return $paginator;
    }

    /**
     * Get all attendances for a specific date within the viewer's scope.
     */
    public function getDailyDetails(User $viewer, string $date, array $filters = []): Collection
    {
        $query = Attendance::with(['user', 'approver', 'approvals.approver'])
            ->whereDate('attendance_date', $date);

        if (!$viewer->isAdmin()) {
            $query->where('user_id', '!=', $viewer->id)
                ->where(function ($q) use ($viewer) {
                    $q->whereHas('approvals', function ($sub) use ($viewer) {
                        $sub->where('approver_id', $viewer->id);
                    })
                    ->orWhereHas('user.approvalProcesses', function ($sub) use ($viewer) {
                        $sub->where('approver_id', $viewer->id)->where('is_active', true);
                    });
                });
        }

        if (!empty($filters['prayer_type'])) {
            $query->where('prayer_type', $filters['prayer_type']);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'pending') {
                $query->whereIn('status', ['pending', 'pending_approval']);
            } elseif ($filters['status'] === 'approved') {
                $query->whereIn('status', ['approved', 'present', 'late']);
            } elseif ($filters['status'] === 'rejected') {
                $query->where('status', 'rejected');
            } elseif ($filters['status'] === 'absent') {
                $query->where('status', 'absent');
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('server_timestamp', 'desc')->get()->map(function ($att) use ($viewer) {
            $statusValue = $att->status->value ?? $att->status;
            $normalizedStatus = match ($statusValue) {
                'pending', 'pending_approval' => 'pending',
                'approved', 'present', 'late' => 'approved',
                'rejected' => 'rejected',
                'absent' => 'absent',
                default => 'pending',
            };

            return [
                'id' => $att->id,
                'uuid' => $att->uuid,
                'user_id' => $att->user_id,
                'user_name' => $att->user?->name ?? 'User',
                'user_email' => $att->user?->email,
                'user_avatar' => $att->user?->avatar_path,
                'prayer_type' => $att->prayer_type->value,
                'prayer_label' => $att->prayer_type->label(),
                'attendance_date' => $att->attendance_date->format('d M Y'),
                'time' => $att->server_timestamp->format('H:i'),
                'status' => $normalizedStatus,
                'status_raw' => $statusValue,
                'status_label' => $att->status->label(),
                'status_color' => $att->status->color(),
                'approval_source' => $att->approval_source,
                'has_photo' => !empty($att->photo_path),
                'photo_url' => !empty($att->photo_path) ? route('member.attendance.photo', $att->id) : null,
                'can_approve' => $this->canUserApprove($viewer, $att),
            ];
        });
    }

    /**
     * Get complete verification detail for an attendance.
     */
    public function getAttendanceVerificationDetail(User $viewer, Attendance $attendance): array
    {
        $attendance->loadMissing(['user', 'approver', 'approvals.approver', 'mosque']);
        $user = $attendance->user;

        // Schedule on that date
        $schedule = null;
        if ($user) {
            $schedule = $this->prayerService->getScheduleForUser($user, $attendance->attendance_date->toDateString());
        }

        $prayerTime = $schedule ? $schedule->getTimeFor($attendance->prayer_type->value) : null;
        $durationMinutes = $user ? $user->getAttendanceDurationMinutes() : 60;
        $windowStart = $prayerTime ? Carbon::parse("{$attendance->attendance_date->toDateString()} {$prayerTime}", $user->getTimezone()) : null;
        $windowEnd = $windowStart ? $windowStart->copy()->addMinutes($durationMinutes) : null;

        $isWithinWindow = false;
        if ($windowStart && $windowEnd) {
            $isWithinWindow = $attendance->server_timestamp->gte($windowStart) && $attendance->server_timestamp->lte($windowEnd);
        }

        $statusValue = $attendance->status->value ?? $attendance->status;
        $normalizedStatus = match ($statusValue) {
            'pending', 'pending_approval' => 'pending',
            'approved', 'present', 'late' => 'approved',
            'rejected' => 'rejected',
            default => 'pending',
        };

        // Approval History Timeline
        $history = $attendance->approvals->map(function ($appr) {
            return [
                'id' => $appr->id,
                'level' => $appr->approval_level,
                'approver_name' => $appr->approver?->name ?? 'Approver',
                'approver_email' => $appr->approver?->email,
                'status' => $appr->status,
                'approval_source' => $appr->approval_source,
                'source_label' => $appr->approval_source === 'system' ? 'Disetujui oleh Sistem' : ($appr->approval_source === 'approver' ? 'Disetujui oleh Approver' : null),
                'note' => $appr->note,
                'approved_at' => $appr->approved_at ? $appr->approved_at->format('d M Y H:i') : null,
                'rejected_at' => $appr->rejected_at ? $appr->rejected_at->format('d M Y H:i') : null,
                'is_system' => $appr->approval_source === 'system',
            ];
        });

        return [
            'id' => $attendance->id,
            'uuid' => $attendance->uuid,
            'status' => $normalizedStatus,
            'status_raw' => $statusValue,
            'status_label' => $attendance->status->label(),
            'status_color' => $attendance->status->color(),
            'approval_source' => $attendance->approval_source,
            'is_system_approved' => $attendance->approval_source === 'system',
            'rejection_reason' => $attendance->rejection_reason,
            'request_notes' => $attendance->request_notes,
            'approval_notes' => $attendance->approval_notes,
            'user' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'email' => $user?->email,
                'avatar_path' => $user?->avatar_path,
                'city' => $user?->getCity(),
            ],
            'attendance_info' => [
                'date' => $attendance->attendance_date->format('d M Y'),
                'date_raw' => $attendance->attendance_date->toDateString(),
                'prayer_type' => $attendance->prayer_type->value,
                'prayer_label' => $attendance->prayer_type->label(),
                'attendance_time' => $attendance->server_timestamp->format('H:i:s'),
                'server_timestamp' => $attendance->server_timestamp->toIso8601String(),
                'created_at' => $attendance->created_at->format('d M Y H:i:s'),
            ],
            'photo' => [
                'has_photo' => !empty($attendance->photo_path),
                'url' => !empty($attendance->photo_path) ? route('member.attendance.photo', $attendance->id) : null,
            ],
            'gps' => [
                'latitude' => $attendance->latitude,
                'longitude' => $attendance->longitude,
                'accuracy' => $attendance->gps_accuracy,
                'is_low_accuracy' => ($attendance->gps_accuracy && $attendance->gps_accuracy > 100),
                'distance_meters' => $attendance->distance_meters,
                'location_name' => $attendance->metadata['location_name'] ?? null,
                'address' => $attendance->metadata['address'] ?? null,
            ],
            'prayer_schedule' => [
                'prayer_time' => $prayerTime ? substr($prayerTime, 0, 5) : '-',
                'window_start' => $windowStart ? $windowStart->format('H:i') : '-',
                'window_end' => $windowEnd ? $windowEnd->format('H:i') : '-',
                'is_within_window' => $isWithinWindow,
                'duration_minutes' => $durationMinutes,
            ],
            'approval_history' => $history,
            'can_approve' => $this->canUserApprove($viewer, $attendance),
            'can_reject' => $this->canUserApprove($viewer, $attendance),
        ];
    }
}
