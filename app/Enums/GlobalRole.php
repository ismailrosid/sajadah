<?php

namespace App\Enums;

enum GlobalRole: string
{
    case Admin = 'admin';
    case SuperAdmin = 'super_admin'; // Backward compatibility
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin, self::SuperAdmin => 'Admin',
            self::User => 'User',
        };
    }
}
