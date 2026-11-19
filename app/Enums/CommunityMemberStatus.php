<?php

namespace App\Enums;

enum CommunityMemberStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Pending => 'Menunggu Persetujuan',
            self::Rejected => 'Ditolak',
            self::Inactive => 'Nonaktif',
        };
    }
}
