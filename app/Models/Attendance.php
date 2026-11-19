<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\PrayerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'community_id',
        'mosque_id',
        'prayer_type',
        'attendance_date',
        'photo_path',
        'latitude',
        'longitude',
        'gps_accuracy',
        'distance_meters',
        'server_timestamp',
        'status',
        'approval_source',
        'rejection_reason',
        'request_notes',
        'approved_by',
        'approved_at',
        'approval_notes',
        'risk_score',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'prayer_type' => PrayerType::class,
            'attendance_date' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
            'gps_accuracy' => 'float',
            'distance_meters' => 'float',
            'server_timestamp' => 'datetime',
            'approved_at' => 'datetime',
            'status' => AttendanceStatus::class,
            'risk_score' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Attendance $attendance) {
            if (empty($attendance->uuid)) {
                $attendance->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals()
    {
        return $this->hasMany(AttendanceApproval::class)->orderBy('approval_level');
    }

    public function currentApproval()
    {
        return $this->hasOne(AttendanceApproval::class)
            ->where('status', 'pending')
            ->orderBy('approval_level');
    }

    public function isPending(): bool
    {
        return in_array($this->status->value ?? $this->status, ['pending', 'pending_approval']);
    }

    public function isApproved(): bool
    {
        return in_array($this->status->value ?? $this->status, ['present', 'approved', 'late']);
    }

    public function isRejected(): bool
    {
        return ($this->status->value ?? $this->status) === 'rejected';
    }

    public function isApprovedByApprover(): bool
    {
        return !empty($this->approved_by)
            || $this->approval_source === 'approver'
            || ($this->relationLoaded('approvals')
                ? $this->approvals->whereNotNull('approver_id')->where('status', 'approved')->isNotEmpty()
                : $this->approvals()->whereNotNull('approver_id')->where('status', 'approved')->exists());
    }

    public function isRejectedByApprover(): bool
    {
        return $this->isRejected();
    }
}
