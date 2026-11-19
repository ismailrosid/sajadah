<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\GlobalRole;
use App\Enums\PrayerType;
use App\Models\Attendance;
use App\Models\PrayerSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonalAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create([
            'name' => 'Fulan Personal',
            'email' => 'fulan.personal@example.com',
            'city' => 'Jakarta Selatan',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'timezone' => 'Asia/Jakarta',
            'prayer_calc_method' => 'KEMENAG',
            'attendance_duration_minutes' => 60,
            'global_role' => GlobalRole::User,
        ]);

        // Fix user prayer schedule for today
        PrayerSchedule::create([
            'user_id' => $this->user->id,
            'city' => 'Jakarta Selatan',
            'schedule_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'fajr' => '04:15:00',
            'dhuhr' => '11:45:00',
            'asr' => '15:05:00',
            'maghrib' => '17:55:00',
            'isha' => '19:08:00',
            'calculation_method' => 'KEMENAG',
        ]);
    }

    public function test_user_can_check_in_personally_within_prayer_window(): void
    {
        // Subuh starts at 04:15, duration 60 mins -> ends at 05:15
        $simulatedNow = Carbon::today('Asia/Jakarta')->setTime(4, 30, 0);
        Carbon::setTestNow($simulatedNow);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($this->user)->post(route('member.attendance.store'), [
            'prayer_type' => 'subuh',
            'latitude' => -6.21300000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 8.5,
            'photo' => $photo,
        ]);

        $response->assertRedirect(route('member.attendance.create'));
        $response->assertSessionHas('info');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'community_id' => null,
            'mosque_id' => null,
            'prayer_type' => 'subuh',
            'latitude' => -6.21300000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 8.5,
            'status' => AttendanceStatus::Present->value,
        ]);

        $attendance = Attendance::where('user_id', $this->user->id)->first();
        $this->assertNotNull($attendance->photo_path);
        Storage::disk('local')->assertExists($attendance->photo_path);

        Carbon::setTestNow();
    }

    public function test_personal_check_in_before_window_is_rejected(): void
    {
        // Freeze time before Subuh (04:00:00)
        $simulatedNow = Carbon::today('Asia/Jakarta')->setTime(4, 0, 0);
        Carbon::setTestNow($simulatedNow);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($this->user)->post(route('member.attendance.store'), [
            'prayer_type' => 'subuh',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 10,
            'photo' => $photo,
        ]);

        $response->assertSessionHasErrors(['prayer_type']);
        $this->assertDatabaseCount('attendances', 0);

        Carbon::setTestNow();
    }

    public function test_personal_normal_check_in_after_cutoff_is_rejected(): void
    {
        // Freeze time after cutoff (05:30:00 > 05:15:00)
        $simulatedNow = Carbon::today('Asia/Jakarta')->setTime(5, 30, 0);
        Carbon::setTestNow($simulatedNow);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($this->user)->post(route('member.attendance.store'), [
            'prayer_type' => 'subuh',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 10,
            'photo' => $photo,
        ]);

        $response->assertSessionHasErrors(['prayer_type']);
        $this->assertDatabaseCount('attendances', 0);

        Carbon::setTestNow();
    }

    public function test_personal_late_attendance_request_records_as_pending_approval(): void
    {
        // Freeze time after cutoff (05:30:00)
        $simulatedNow = Carbon::today('Asia/Jakarta')->setTime(5, 30, 0);
        Carbon::setTestNow($simulatedNow);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($this->user)->post(route('member.attendance.store'), [
            'prayer_type' => 'subuh',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 12.0,
            'photo' => $photo,
            'is_late_request' => true,
            'request_notes' => 'Ada kendala di perjalanan, sholat terlambat beberapa menit.',
        ]);

        $response->assertRedirect(route('member.attendance.create'));
        $response->assertSessionHas('info');

        // Verify it was recorded as 'pending_approval' waiting for admin approval
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'community_id' => null,
            'mosque_id' => null,
            'prayer_type' => 'subuh',
            'status' => AttendanceStatus::PendingApproval->value,
            'request_notes' => 'Ada kendala di perjalanan, sholat terlambat beberapa menit.',
        ]);

        Carbon::setTestNow();
    }

    public function test_prevents_duplicate_personal_attendance_for_same_prayer_on_same_day(): void
    {
        $simulatedNow = Carbon::today('Asia/Jakarta')->setTime(4, 30, 0);
        Carbon::setTestNow($simulatedNow);

        Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user->id,
            'community_id' => null,
            'mosque_id' => null,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 5.0,
            'server_timestamp' => now('Asia/Jakarta'),
            'status' => AttendanceStatus::Present,
            'risk_score' => 0,
        ]);

        $photo = UploadedFile::fake()->image('selfie2.jpg');

        $response = $this->actingAs($this->user)->post(route('member.attendance.store'), [
            'prayer_type' => 'subuh',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 5.0,
            'photo' => $photo,
        ]);

        $response->assertSessionHasErrors(['prayer_type']);
        $this->assertDatabaseCount('attendances', 1);

        Carbon::setTestNow();
    }

    public function test_user_can_update_personal_prayer_preferences_in_profile(): void
    {
        $response = $this->actingAs($this->user)->patch(route('profile.update'), [
            'name' => 'Fulan Updated',
            'email' => 'fulan.personal@example.com',
            'city' => 'Bandung',
            'latitude' => -6.917464,
            'longitude' => 107.619122,
            'timezone' => 'Asia/Jakarta',
            'prayer_calc_method' => 'MWL',
            'attendance_duration_minutes' => 45,
        ]);

        $response->assertRedirect(route('profile.edit'));

        $this->user->refresh();
        $this->assertEquals('Fulan Updated', $this->user->name);
        $this->assertEquals('Bandung', $this->user->city);
        $this->assertEquals(-6.917464, $this->user->latitude);
        $this->assertEquals(107.619122, $this->user->longitude);
        $this->assertEquals('MWL', $this->user->prayer_calc_method);
        $this->assertEquals(45, $this->user->attendance_duration_minutes);
    }

    public function test_personal_dashboard_displays_prayer_schedule_and_streak(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Member/Dashboard')
            ->has('prayers')
            ->has('weeklyStats')
            ->has('todayAttendancesCount')
            ->has('user')
            ->where('user.city', 'Jakarta Selatan')
        );
    }
}
