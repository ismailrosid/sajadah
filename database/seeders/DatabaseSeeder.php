<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\CommunityMemberRole;
use App\Enums\CommunityMemberStatus;
use App\Enums\GlobalRole;
use App\Enums\PrayerType;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\Mosque;
use App\Models\PrayerSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@sholatin.test',
            'password' => Hash::make('password'),
            'phone' => '+6281234567890',
            'global_role' => GlobalRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);

        // 2. Community Admin
        $communityAdmin = User::create([
            'name' => 'Ustadz H. Abdullah (Admin)',
            'email' => 'admin@alikhlas.test',
            'password' => Hash::make('password'),
            'phone' => '+6281298765432',
            'global_role' => GlobalRole::User,
            'email_verified_at' => now(),
        ]);

        // 3. Regular Members
        $member1 = User::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@sholatin.test',
            'password' => Hash::make('password'),
            'phone' => '+6281311112222',
            'global_role' => GlobalRole::User,
            'email_verified_at' => now(),
        ]);

        $member2 = User::create([
            'name' => 'Fathur Rahman',
            'email' => 'fathur@sholatin.test',
            'password' => Hash::make('password'),
            'phone' => '+6281333334444',
            'global_role' => GlobalRole::User,
            'email_verified_at' => now(),
        ]);

        $member3 = User::create([
            'name' => 'Salman Al-Farisi',
            'email' => 'salman@sholatin.test',
            'password' => Hash::make('password'),
            'phone' => '+6281355556666',
            'global_role' => GlobalRole::User,
            'email_verified_at' => now(),
        ]);

        $member4 = User::create([
            'name' => 'Aisyah Putri',
            'email' => 'aisyah@sholatin.test',
            'password' => Hash::make('password'),
            'phone' => '+6281377778888',
            'global_role' => GlobalRole::User,
            'email_verified_at' => now(),
        ]);

        // 4. Create Communities
        $community1 = Community::create([
            'name' => 'Komunitas Jamaah Al-Ikhlas',
            'slug' => 'al-ikhlas',
            'description' => 'Komunitas jamaah sholat lima waktu Masjid Jami Al-Ikhlas Menteng.',
            'address' => 'Jl. Menteng Raya No. 15, Jakarta Pusat',
            'timezone' => 'Asia/Jakarta',
            'status' => 'active',
        ]);

        $community2 = Community::create([
            'name' => 'Rohis SMAN 1 Jakarta',
            'slug' => 'rohis-sman1',
            'description' => 'Komunitas pembiasaan sholat berjamaah pelajar Rohis SMAN 1 Jakarta.',
            'address' => 'Jl. Budi Utomo No. 7, Sawah Besar, Jakarta Pusat',
            'timezone' => 'Asia/Jakarta',
            'status' => 'active',
        ]);

        // 5. Assign Members to Community 1
        CommunityMember::create([
            'community_id' => $community1->id,
            'user_id' => $communityAdmin->id,
            'role' => CommunityMemberRole::Admin,
            'status' => CommunityMemberStatus::Active,
            'joined_at' => now()->subMonths(3),
        ]);

        CommunityMember::create([
            'community_id' => $community1->id,
            'user_id' => $member1->id,
            'role' => CommunityMemberRole::Member,
            'status' => CommunityMemberStatus::Active,
            'joined_at' => now()->subMonths(2),
        ]);

        CommunityMember::create([
            'community_id' => $community1->id,
            'user_id' => $member2->id,
            'role' => CommunityMemberRole::Member,
            'status' => CommunityMemberStatus::Active,
            'joined_at' => now()->subMonths(1),
        ]);

        CommunityMember::create([
            'community_id' => $community1->id,
            'user_id' => $member3->id,
            'role' => CommunityMemberRole::Member,
            'status' => CommunityMemberStatus::Active,
            'joined_at' => now()->subWeeks(2),
        ]);

        CommunityMember::create([
            'community_id' => $community1->id,
            'user_id' => $member4->id,
            'role' => CommunityMemberRole::Member,
            'status' => CommunityMemberStatus::Pending, // Pending verification
            'joined_at' => null,
        ]);

        // Member 1 also in Community 2 (demonstrating multi-community support)
        CommunityMember::create([
            'community_id' => $community2->id,
            'user_id' => $member1->id,
            'role' => CommunityMemberRole::Admin,
            'status' => CommunityMemberStatus::Active,
            'joined_at' => now()->subMonth(),
        ]);

        // 6. Mosques
        $mosque1 = Mosque::create([
            'community_id' => $community1->id,
            'name' => 'Masjid Jami Al-Ikhlas',
            'address' => 'Jl. Menteng Raya No. 15, Jakarta Pusat',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'radius_meters' => 120,
            'is_active' => true,
            'is_default' => true,
            'description' => 'Masjid utama dengan kapasitas 1.000 jamaah.',
        ]);

        $mosque2 = Mosque::create([
            'community_id' => $community1->id,
            'name' => 'Musholla Ar-Rahman',
            'address' => 'Jl. Cut Meutia No. 4, Jakarta Pusat',
            'latitude' => -6.21150000,
            'longitude' => 106.84320000,
            'radius_meters' => 60,
            'is_active' => true,
            'is_default' => false,
            'description' => 'Musholla cabang untuk sholat dzuhur dan ashar.',
        ]);

        $mosque3 = Mosque::create([
            'community_id' => $community2->id,
            'name' => 'Masjid Nurul Ilmi SMAN 1',
            'address' => 'Komplek SMAN 1 Jakarta, Jl. Budi Utomo No. 7',
            'latitude' => -6.16680000,
            'longitude' => 106.83670000,
            'radius_meters' => 100,
            'is_active' => true,
            'is_default' => true,
            'description' => 'Masjid sekolah SMAN 1.',
        ]);

        // 7. Update Attendance Settings
        $community1->setting()->update([
            'default_radius_meters' => 120,
            'opening_buffer_minutes' => 30,
            'closing_buffer_minutes' => 60,
            'late_tolerance_minutes' => 20,
            'prayer_calc_method' => 'KEMENAG',
            'is_attendance_enabled' => true,
            'require_selfie' => true,
            'require_gps' => true,
        ]);

        // 8. Generate Prayer Schedules for the current month (-7 days to +14 days)
        $today = Carbon::today();
        for ($i = -7; $i <= 14; $i++) {
            $date = $today->copy()->addDays($i);
            
            // Standard Jakarta prayer times baseline with minute variations
            PrayerSchedule::create([
                'community_id' => $community1->id,
                'mosque_id' => $mosque1->id,
                'schedule_date' => $date->toDateString(),
                'fajr' => '04:15:00',
                'dhuhr' => '11:45:00',
                'asr' => '15:05:00',
                'maghrib' => '17:55:00',
                'isha' => '19:08:00',
                'calculation_method' => 'KEMENAG',
            ]);

            PrayerSchedule::create([
                'community_id' => $community2->id,
                'mosque_id' => $mosque3->id,
                'schedule_date' => $date->toDateString(),
                'fajr' => '04:15:00',
                'dhuhr' => '11:45:00',
                'asr' => '15:05:00',
                'maghrib' => '17:55:00',
                'isha' => '19:08:00',
                'calculation_method' => 'KEMENAG',
            ]);
        }

        // 9. Seed Sample Attendance Records for Member 1 (Ahmad Fauzi) to establish streak & statistics
        $prayerTypes = [PrayerType::Subuh, PrayerType::Dzuhur, PrayerType::Ashar, PrayerType::Maghrib, PrayerType::Isya];
        
        for ($day = 6; $day >= 1; $day--) {
            $pastDate = $today->copy()->subDays($day);
            foreach ($prayerTypes as $index => $prayer) {
                Attendance::create([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $member1->id,
                    'community_id' => $community1->id,
                    'mosque_id' => $mosque1->id,
                    'prayer_type' => $prayer,
                    'attendance_date' => $pastDate->toDateString(),
                    'photo_path' => null,
                    'latitude' => -6.20881000 + ($index * 0.00001),
                    'longitude' => 106.84561000 + ($index * 0.00001),
                    'gps_accuracy' => 12.5,
                    'distance_meters' => 15.2,
                    'server_timestamp' => $pastDate->copy()->setTime(4 + ($index * 3), 20),
                    'status' => AttendanceStatus::Present,
                    'rejection_reason' => null,
                    'risk_score' => 5,
                    'metadata' => ['device' => 'Mobile Browser', 'ip' => '127.0.0.1'],
                ]);
            }
        }

        // Today's attendances for member 1
        Attendance::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $member1->id,
            'community_id' => $community1->id,
            'mosque_id' => $mosque1->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => $today->toDateString(),
            'photo_path' => null,
            'latitude' => -6.20880500,
            'longitude' => 106.84560500,
            'gps_accuracy' => 8.4,
            'distance_meters' => 8.1,
            'server_timestamp' => $today->copy()->setTime(4, 25),
            'status' => AttendanceStatus::Present,
            'rejection_reason' => null,
            'risk_score' => 2,
            'metadata' => ['device' => 'Chrome Mobile', 'ip' => '127.0.0.1'],
        ]);

        Attendance::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $member1->id,
            'community_id' => $community1->id,
            'mosque_id' => $mosque1->id,
            'prayer_type' => PrayerType::Dzuhur,
            'attendance_date' => $today->toDateString(),
            'photo_path' => null,
            'latitude' => -6.20882000,
            'longitude' => 106.84563000,
            'gps_accuracy' => 15.0,
            'distance_meters' => 24.5,
            'server_timestamp' => $today->copy()->setTime(11, 55),
            'status' => AttendanceStatus::Late,
            'rejection_reason' => null,
            'risk_score' => 10,
            'metadata' => ['device' => 'Chrome Mobile', 'ip' => '127.0.0.1'],
        ]);

        // Today's attendance for Member 2 (Fathur Rahman)
        Attendance::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $member2->id,
            'community_id' => $community1->id,
            'mosque_id' => $mosque1->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => $today->toDateString(),
            'photo_path' => null,
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 5.2,
            'distance_meters' => 12.0,
            'server_timestamp' => $today->copy()->setTime(4, 20),
            'status' => AttendanceStatus::Present,
            'rejection_reason' => null,
            'risk_score' => 0,
            'metadata' => ['device' => 'Safari iOS', 'ip' => '127.0.0.1'],
        ]);
    }
}
