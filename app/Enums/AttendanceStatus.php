<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Rejected = 'rejected';
    case Absent = 'absent';
    case PendingApproval = 'pending_approval';
    case Pending = 'pending';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Present, self::Approved => 'Tepat Waktu',
            self::Late => 'Terlambat',
            self::Rejected, self::Absent => 'Tidak Sholat',
            self::PendingApproval, self::Pending => 'Menunggu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present, self::Approved => 'emerald',
            self::Late => 'amber',
            self::Rejected, self::Absent => 'rose',
            self::PendingApproval, self::Pending => 'amber',
        };
    }
}
