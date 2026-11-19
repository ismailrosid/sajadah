<?php

namespace App\Enums;

enum CommunityMemberRole: string
{
    case Admin = 'community_admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Community Admin',
            self::Member => 'Member',
        };
    }
}
