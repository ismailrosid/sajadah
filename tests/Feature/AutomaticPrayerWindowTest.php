<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AutomaticPrayerWindowTest extends TestCase
{
    use RefreshDatabase;

    protected User $member;
    protected User $admin;
    protected Community $community;
    protected Mosque $mosque;
    protected AttendanceSetting $setting;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->community = Community::create([
            'name' => 'Komunitas Al-Ikhlas',
            'slug' => 'al-ikhlas',
            'timezone' => 'Asia/Jakarta',
            'status' => 'active',
            'reference_city' => 'Jakarta Selatan',
            'reference_latitude' => -6.2088,
            'reference_longitude' => 106.8456,
        ]);

        $this->setting = $this->community->setting;
        $this->setting->update([
            'default_radius_meters' => 100,
            'attendance_duration_minutes' => 60,
            'prayer_calc_method' => 'KEMENAG',
            'is_attendance_enabled' => true,
            'require_selfie' => true,
            'require_gps' => true,
        ]);

        $this->mosque = Mosque::create([
            'community_id' => $this->community->id,
            'name' => 'Masjid Al-Ikhlas Utama',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'radius_meters' => 100,
            'is_active' => true,
            'is_default' => true,
        ]);

        // Fix prayer times for today
        PrayerSchedule::create([
            'community_id' => $this->community->id,
            'mosque_id' => $this->mosque->id,
            'schedule_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'fajr' => '04:15:00',
            'dhuhr' => '11:45:00',
            'asr' => '15:05:00',
            'maghrib' => '17:55:00',
            'isha' => '19:08:00',
            'calculation_method' => 'KEMENAG',
        ]);

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

    public function test_attendance_within_window_is_recorded_as_present(): void
    {
        // Subuh starts at 04:15:00, window ends at 05:15:00 (60 mins)
        // Freeze time at 04:30:00 (within window)
        $simulatedNow = Carbon::today('Asia/Jakarta')->setTime(4, 30, 0);
        Carbon::setTestNow($simulatedNow);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($this->member)->post(route('member.attendance.store'), [
            'mosque_id' => $this->mosque->id,
            'prayer_type' => 'subuh',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 10,
            'photo' => $photo,
        ]);

        $response->assertSessionHas('info');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->member->id,
            'community_id' => $this->community->id,
            'prayer_type' => 'subuh',
            'status' => AttendanceStatus::Present->value,
        ]);

        Carbon::setTestNow();
    }

    public function test_attendance_before_prayer_window_is_rejected(): void
    {
        // Freeze time before Subuh (04:00:00)
        $simulatedNow = Carbon::today('Asia/Jakarta')->setTime(4, 0, 0);
        Carbon::setTestNow($simulatedNow);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($this->member)->post(route('member.attendance.store'), [
            'mosque_id' => $this->mosque->id,
            'prayer_type' => 'subuh',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 10,
            'photo' => $photo,
        ]);

        $response->assertSessionHasErrors(['prayer_type']);

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->member->id,
            'community_id' => $this->community->id,
            'prayer_type' => 'subuh',
        ]);

        Carbon::setTestNow();
    }

    public function test_normal_attendance_after_cutoff_is_rejected(): void
    {
        // Freeze time after cutoff (05:30:00 > 05:15:00)
        $simulatedNow = Carbon::today('Asia/Jakarta')->setTime(5, 30, 0);
        Carbon::setTestNow($simulatedNow);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($this->member)->post(route('member.attendance.store'), [
            'mosque_id' => $this->mosque->id,
            'prayer_type' => 'subuh',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 10,
            'photo' => $photo,
        ]);

        $response->assertSessionHasErrors(['prayer_type']);

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->member->id,
            'community_id' => $this->community->id,
            'prayer_type' => 'subuh',
        ]);

        Carbon::setTestNow();
    }

    public function test_late_attendance_request_after_cutoff_creates_pending_approval(): void
    {
        // Freeze time after cutoff (05:30:00 > 05:15:00)
        $simulatedNow = Carbon::today('Asia/Jakarta')->setTime(5, 30, 0);
        Carbon::setTestNow($simulatedNow);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($this->member)->post(route('member.attendance.store'), [
            'mosque_id' => $this->mosque->id,
            'prayer_type' => 'subuh',
            'latitude' => -6.20880000,
            'longitude' => 106.84560000,
            'gps_accuracy' => 10,
            'photo' => $photo,
            'is_late_request' => true,
            'request_notes' => 'Kendala ban motor bocor di perjalanan ke masjid.',
        ]);

        $response->assertSessionHas('info');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->member->id,
            'community_id' => $this->community->id,
            'prayer_type' => 'subuh',
            'status' => AttendanceStatus::PendingApproval->value,
            'request_notes' => 'Kendala ban motor bocor di perjalanan ke masjid.',
        ]);

        Carbon::setTestNow();
    }

    public function test_admin_can_approve_late_attendance_request(): void
    {
        $attendance = Attendance::create([
            'user_id' => $this->member->id,
            'community_id' => $this->community->id,
            'mosque_id' => $this->mosque->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'server_timestamp' => Carbon::today('Asia/Jakarta')->setTime(5, 35, 0),
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'distance_meters' => 5,
            'status' => AttendanceStatus::PendingApproval,
            'request_notes' => 'Terkendala macet parah.',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.attendances.approve', $attendance), [
            'approval_notes' => 'Disetujui atas udzur syari yang jelas.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertEquals(AttendanceStatus::Late, $attendance->status);
        $this->assertEquals($this->admin->id, $attendance->approved_by);
        $this->assertNotNull($attendance->approved_at);
        $this->assertEquals('Disetujui atas udzur syari yang jelas.', $attendance->approval_notes);
    }

    public function test_admin_can_reject_late_attendance_request(): void
    {
        $attendance = Attendance::create([
            'user_id' => $this->member->id,
            'community_id' => $this->community->id,
            'mosque_id' => $this->mosque->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'server_timestamp' => Carbon::today('Asia/Jakarta')->setTime(5, 35, 0),
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'distance_meters' => 5,
            'status' => AttendanceStatus::PendingApproval,
            'request_notes' => 'Terlambat bangun.',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.attendances.reject', $attendance), [
            'rejection_reason' => 'Alasan tidak memenuhi ketentuan toleransi komunitas.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertEquals(AttendanceStatus::Rejected, $attendance->status);
        $this->assertStringContainsString('Alasan tidak memenuhi', $attendance->rejection_reason);
    }
}
