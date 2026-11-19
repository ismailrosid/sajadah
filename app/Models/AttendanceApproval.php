<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'approver_id',
        'approval_level',
        'status',
        'approval_source',
        'note',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'approval_level' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSystemApproved(): bool
    {
        return $this->isApproved() && $this->approval_source === 'system';
    }
}
