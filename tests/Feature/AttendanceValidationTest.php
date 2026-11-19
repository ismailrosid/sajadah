<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\CommunityMemberRole;
use App\Enums\CommunityMemberStatus;
use App\Enums\GlobalRole;
use App\Enums\PrayerType;
use App\Models\Attendance;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\Mosque;
use App\Models\PrayerSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $member;
    protected User $admin;
    protected Community $community;
    protected Mosque $mosque;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Community setup
        $this->community = Community::create([
            'name' => 'Masjid Raya Test',
            'slug' => 'masjid-raya-test',
            'timezone' => 'Asia/Jakarta',
            'status' => 'active',
        ]);

        // Mosque setup (Menteng Jakarta)
        $this->mosque = Mosque::create([
            'community_id' => $this->community->id,
            'name' => 'Masjid Utama',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'radius_meters' => 100,
            'is_active' => true,
            'is_default' => true,
        ]);

        // Prayer Schedule for today
        PrayerSchedule::create([
            'community_id' => $this->community->id,
            'mosque_id' => $this->mosque->id,
            'schedule_date' => Carbon::today()->toDateString(),
            'fajr' => '04:15:00',
            'dhuhr' => '11:45:00',
            'asr' => '15:05:00',
            'maghrib' => '17:55:00',
            'isha' => '19:08:00',
            'calculation_method' => 'KEMENAG',
        ]);

        // Users setup
        $this->member = User::factory()->create([
            'global_role' => GlobalRole::User,
        ]);

        CommunityMember::create([
            'community_id' => $this->community->id,
            'user_id' => $this->member->id,
            'role' => CommunityMemberRole::Member,
            'status' => CommunityMemberStatus::Active,
            'joined_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'global_role' => GlobalRole::User,
        ]);

        CommunityMember::create([
            'community_id' => $this->community->id,
            'user_id' => $this->admin->id,
            'role' => CommunityMemberRole::Admin,
            'status' => CommunityMemberStatus::Active,
            'joined_at' => now(),
        ]);
    }

    public function test_attendance_records_location_coordinates_without_radius_rejection(): void
    {
        // Freeze time within Subuh window (04:15 - 05:15)
        $simulatedNow = Carbon::today('Asia/Jakarta')->setTime(4, 30, 0);
        Carbon::setTestNow($simulatedNow);

        // Location coordinates captured to record where user took attendance
        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($this->member)
            ->withSession(['active_community_id' => $this->community->id])
            ->post(route('member.attendance.store'), [
                'prayer_type' => 'subuh',
                'latitude' => -6.21300000,
                'longitude' => 106.84560000,
                'gps_accuracy' => 10.0,
                'photo' => $photo,
            ]);

        $response->assertRedirect(route('member.attendance.create'));
        $response->assertSessionHas('info');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->member->id,
            'community_id' => $this->community->id,
            'prayer_type' => 'subuh',
            'latitude' => -6.21300000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 10.0,
            'status' => AttendanceStatus::Present->value,
        ]);

        Carbon::setTestNow();
    }

    public function test_prevents_duplicate_attendance_for_same_prayer_on_same_day(): void
    {
        Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->member->id,
            'community_id' => $this->community->id,
            'mosque_id' => $this->mosque->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::today()->toDateString(),
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'distance_meters' => 5.0,
            'server_timestamp' => now(),
            'status' => AttendanceStatus::Present,
            'risk_score' => 0,
        ]);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($this->member)
            ->withSession(['active_community_id' => $this->community->id])
            ->post(route('member.attendance.store'), [
                'mosque_id' => $this->mosque->id,
                'prayer_type' => 'subuh',
                'latitude' => -6.20880000,
                'longitude' => 106.84560000,
                'gps_accuracy' => 5.0,
                'photo' => $photo,
            ]);

        $response->assertSessionHasErrors(['prayer_type']);
    }

    public function test_member_cannot_access_community_admin_panel(): void
    {
        $response = $this->actingAs($this->member)
            ->withSession(['active_community_id' => $this->community->id])
            ->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_community_admin_panel(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_community_id' => $this->community->id])
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }
}
