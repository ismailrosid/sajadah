<?php

namespace App\Models;

use App\Enums\CommunityMemberRole;
use App\Enums\CommunityMemberStatus;
use App\Enums\GlobalRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_path',
        'global_role',
        'city',
        'latitude',
        'longitude',
        'timezone',
        'prayer_calc_method',
        'attendance_duration_minutes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'global_role' => GlobalRole::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'attendance_duration_minutes' => 'integer',
        ];
    }

    public function getLatitude(): float
    {
        return $this->latitude !== null ? (float) $this->latitude : -6.20880000;
    }

    public function getLongitude(): float
    {
        return $this->longitude !== null ? (float) $this->longitude : 106.84560000;
    }

    public function getTimezone(): string
    {
        return $this->timezone ?: 'Asia/Jakarta';
    }

    public function getPrayerCalcMethod(): string
    {
        return $this->prayer_calc_method ?: 'KEMENAG';
    }

    public function getAttendanceDurationMinutes(): int
    {
        return $this->attendance_duration_minutes ?: 60;
    }

    public function getCity(): string
    {
        return $this->city ?: 'Jakarta';
    }

    public function communityMembers(): HasMany
    {
        return $this->hasMany(CommunityMember::class);
    }

    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_members')
            ->withPivot(['id', 'role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function activeCommunities(): BelongsToMany
    {
        return $this->communities()
            ->wherePivot('status', CommunityMemberStatus::Active->value);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function approvalProcesses(): HasMany
    {
        return $this->hasMany(ApprovalProcess::class, 'user_id');
    }

    public function assignedApprovalProcesses(): HasMany
    {
        return $this->hasMany(ApprovalProcess::class, 'approver_id');
    }

    public function attendanceApprovals(): HasMany
    {
        return $this->hasMany(AttendanceApproval::class, 'approver_id');
    }

    public function isAdmin(): bool
    {
        return $this->global_role === GlobalRole::Admin || $this->global_role === GlobalRole::SuperAdmin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin();
    }

    public function isApprover(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->assignedApprovalProcesses()->where('is_active', true)->exists()
            || $this->attendanceApprovals()->where('status', 'pending')->exists();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isCommunityAdmin(Community|int|null $community = null): bool
    {
        return $this->isAdmin();
    }

    public function isCommunityMember(Community|int|null $community = null): bool
    {
        return true;
    }
}
