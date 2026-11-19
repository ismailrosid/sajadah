<?php

namespace Database\Seeders;

use App\Enums\GlobalRole;
use App\Models\AttendanceSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Admin Utama
        User::updateOrCreate(
            ['email' => 'admin@sajadah.site'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'phone' => '081234567890',
                'global_role' => GlobalRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        // 2. Inisialisasi Pengaturan Absensi Global
        AttendanceSetting::getGlobalSetting();
    }
}
