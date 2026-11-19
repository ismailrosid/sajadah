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
            self::Present => 'Tepat Waktu',
            self::Approved => 'Disetujui',
            self::Late => 'Terlambat (Disetujui)',
            self::Rejected => 'Ditolak',
            self::Absent => 'Tidak Sholat',
            self::PendingApproval, self::Pending => 'Menunggu Persetujuan',
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
