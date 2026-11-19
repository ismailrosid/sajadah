<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\GlobalRole;
use App\Enums\PrayerType;
use App\Models\ApprovalProcess;
use App\Models\ApprovalRole;
use App\Models\Attendance;
use App\Models\AttendanceApproval;
use App\Models\PrayerSchedule;
use App\Models\User;
use App\Services\Approval\AttendanceApprovalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DynamicApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $approver1; // Budi (Supervisor)
    protected User $approver2; // Rizky (Supervisor)
    protected User $approverHigher; // Andi (Manager)
    protected User $user1; // Ahmad (Staff)
    protected User $user2; // Fajar (Staff)
    protected ApprovalRole $staffRole;
    protected ApprovalRole $supervisorRole;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->create([
            'name' => 'Admin Sistem',
            'email' => 'admin@sholatin.test',
            'global_role' => GlobalRole::SuperAdmin,
        ]);

        $this->approver1 = User::factory()->create([
            'name' => 'Budi Supervisor',
            'email' => 'budi@sholatin.test',
            'global_role' => GlobalRole::User,
        ]);

        $this->approver2 = User::factory()->create([
            'name' => 'Rizky Supervisor',
            'email' => 'rizky@sholatin.test',
            'global_role' => GlobalRole::User,
        ]);

        $this->approverHigher = User::factory()->create([
            'name' => 'Andi Manager',
            'email' => 'andi@sholatin.test',
            'global_role' => GlobalRole::User,
        ]);

        $this->user1 = User::factory()->create([
            'name' => 'Ahmad Staff',
            'email' => 'ahmad@sholatin.test',
            'global_role' => GlobalRole::User,
            'attendance_duration_minutes' => 60,
        ]);

        $this->user2 = User::factory()->create([
            'name' => 'Fajar Staff',
            'email' => 'fajar@sholatin.test',
            'global_role' => GlobalRole::User,
            'attendance_duration_minutes' => 60,
        ]);

        $this->staffRole = ApprovalRole::firstOrCreate(
            ['name' => 'Staff'],
            ['description' => 'Tingkat staf', 'is_active' => true]
        );

        $this->supervisorRole = ApprovalRole::firstOrCreate(
            ['name' => 'Supervisor'],
            ['description' => 'Tingkat supervisor', 'is_active' => true]
        );
    }

    public function test_different_users_can_have_different_approvers(): void
    {
        // Ahmad -> Budi (Level 1)
        ApprovalProcess::create([
            'user_id' => $this->user1->id,
            'approver_id' => $this->approver1->id,
            'approval_role_id' => $this->staffRole->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        // Fajar -> Rizky (Level 1)
        ApprovalProcess::create([
            'user_id' => $this->user2->id,
            'approver_id' => $this->approver2->id,
            'approval_role_id' => $this->staffRole->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        // Fix prayer schedule today
        PrayerSchedule::create([
            'user_id' => $this->user1->id,
            'schedule_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'fajr' => '04:15:00',
            'dhuhr' => '11:45:00',
            'asr' => '15:05:00',
            'maghrib' => '17:55:00',
            'isha' => '19:08:00',
            'calculation_method' => 'KEMENAG',
        ]);

        PrayerSchedule::create([
            'user_id' => $this->user2->id,
            'schedule_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'fajr' => '04:15:00',
            'dhuhr' => '11:45:00',
            'asr' => '15:05:00',
            'maghrib' => '17:55:00',
            'isha' => '19:08:00',
            'calculation_method' => 'KEMENAG',
        ]);

        // Freeze time within window
        Carbon::setTestNow(Carbon::today('Asia/Jakarta')->setTime(4, 30, 0));

        // 1. Ahmad submits attendance
        $photo1 = UploadedFile::fake()->image('ahmad_selfie.jpg');
        $this->actingAs($this->user1)->post(route('member.attendance.store'), [
            'prayer_type' => 'subuh',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'gps_accuracy' => 10.0,
            'photo' => $photo1,
        ]);

        // 2. Fajar submits attendance
        $photo2 = UploadedFile::fake()->image('fajar_selfie.jpg');
        $this->actingAs($this->user2)->post(route('member.attendance.store'), [
            'prayer_type' => 'subuh',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'gps_accuracy' => 10.0,
            'photo' => $photo2,
        ]);

        $att1 = Attendance::where('user_id', $this->user1->id)->first();
        $att2 = Attendance::where('user_id', $this->user2->id)->first();

        $this->assertNotNull($att1);
        $this->assertNotNull($att2);

        // Verify Ahmad's approver is Budi
        $this->assertDatabaseHas('attendance_approvals', [
            'attendance_id' => $att1->id,
            'approver_id' => $this->approver1->id,
            'approval_level' => 1,
            'status' => 'pending',
        ]);

        // Verify Fajar's approver is Rizky
        $this->assertDatabaseHas('attendance_approvals', [
            'attendance_id' => $att2->id,
            'approver_id' => $this->approver2->id,
            'approval_level' => 1,
            'status' => 'pending',
        ]);

        Carbon::setTestNow();
    }

    public function test_user_cannot_be_approver_for_themselves(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.approval-config.processes.store'), [
            'user_id' => $this->user1->id,
            'approver_id' => $this->user1->id, // Same user
            'approval_role_id' => $this->staffRole->id,
            'approval_level' => 1,
        ]);

        $response->assertSessionHasErrors(['approver_id']);
        $this->assertDatabaseCount('approval_processes', 0);
    }

    public function test_circular_approval_is_prevented(): void
    {
        // Budi approves Ahmad
        ApprovalProcess::create([
            'user_id' => $this->user1->id,
            'approver_id' => $this->approver1->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        // Now attempt to make Ahmad approve Budi (Circular: Ahmad -> Budi -> Ahmad)
        $response = $this->actingAs($this->admin)->post(route('admin.approval-config.processes.store'), [
            'user_id' => $this->approver1->id,
            'approver_id' => $this->user1->id,
            'approval_level' => 1,
        ]);

        $response->assertSessionHasErrors(['approver_id']);
        $this->assertDatabaseCount('approval_processes', 1);
    }

    public function test_daily_approval_grouping_has_one_row_per_date(): void
    {
        // Create attendances on 2 distinct dates
        $date1 = '2026-11-16';
        $date2 = '2026-11-17';

        // 3 attendances on date 1 (1 pending, 1 approved, 1 rejected)
        Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user1->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => $date1,
            'server_timestamp' => Carbon::parse("{$date1} 04:30:00"),
            'status' => AttendanceStatus::Pending,
        ]);

        Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user2->id,
            'prayer_type' => PrayerType::Dzuhur,
            'attendance_date' => $date1,
            'server_timestamp' => Carbon::parse("{$date1} 12:15:00"),
            'status' => AttendanceStatus::Approved,
        ]);

        Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user1->id,
            'prayer_type' => PrayerType::Ashar,
            'attendance_date' => $date1,
            'server_timestamp' => Carbon::parse("{$date1} 15:30:00"),
            'status' => AttendanceStatus::Rejected,
            'rejection_reason' => 'Foto selfie tidak jelas.',
        ]);

        // 2 attendances on date 2 (2 approved)
        Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user1->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => $date2,
            'server_timestamp' => Carbon::parse("{$date2} 04:30:00"),
            'status' => AttendanceStatus::Approved,
        ]);

        Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user2->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => $date2,
            'server_timestamp' => Carbon::parse("{$date2} 04:35:00"),
            'status' => AttendanceStatus::Approved,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.approvals.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Approvals/Index')
            ->has('dailyGroups.data', 2) // Exactly 2 rows: 1 for date2, 1 for date1
            ->where('dailyGroups.data.0.date_raw', $date2)
            ->where('dailyGroups.data.0.total_attendances', 2)
            ->where('dailyGroups.data.0.approved_count', 2)
            ->where('dailyGroups.data.1.date_raw', $date1)
            ->where('dailyGroups.data.1.total_attendances', 3)
            ->where('dailyGroups.data.1.pending_count', 1)
            ->where('dailyGroups.data.1.approved_count', 1)
            ->where('dailyGroups.data.1.rejected_count', 1)
        );
    }

    public function test_approver_can_view_daily_detail_and_verification(): void
    {
        $date = '2026-11-17';
        $att = Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user1->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => $date,
            'server_timestamp' => Carbon::parse("{$date} 04:30:00"),
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'gps_accuracy' => 15.0,
            'status' => AttendanceStatus::Pending,
        ]);

        AttendanceApproval::create([
            'attendance_id' => $att->id,
            'approver_id' => $this->approver1->id,
            'approval_level' => 1,
            'status' => 'pending',
        ]);

        // Daily Detail page
        $dailyResponse = $this->actingAs($this->approver1)->get(route('admin.approvals.daily', $date));
        $dailyResponse->assertOk();
        $dailyResponse->assertInertia(fn ($page) => $page
            ->component('Admin/Approvals/DailyDetail')
            ->where('date', $date)
            ->has('attendances', 1)
            ->where('attendances.0.id', $att->id)
        );

        // Verification Detail page
        $showResponse = $this->actingAs($this->approver1)->get(route('admin.approvals.show', $att->id));
        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page
            ->component('Admin/Approvals/Show')
            ->where('attendance.id', $att->id)
            ->where('attendance.gps.latitude', -6.2088)
            ->where('attendance.can_approve', true)
        );
    }

    public function test_multi_level_approval_workflow(): void
    {
        // Configure 2-level approval: User1 -> Approver1 (Level 1) -> ApproverHigher (Level 2)
        ApprovalProcess::create([
            'user_id' => $this->user1->id,
            'approver_id' => $this->approver1->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        ApprovalProcess::create([
            'user_id' => $this->user1->id,
            'approver_id' => $this->approverHigher->id,
            'approval_level' => 2,
            'is_active' => true,
        ]);

        $att = Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user1->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => '2026-11-17',
            'server_timestamp' => Carbon::parse('2026-11-17 04:30:00'),
            'status' => AttendanceStatus::Pending,
        ]);

        app(AttendanceApprovalService::class)->initializeApproval($att);

        $this->assertEquals(2, $att->approvals()->count());

        // Level 1 approves (Budi)
        $this->actingAs($this->approver1)->post(route('admin.attendances.approve', $att->id), [
            'approval_notes' => 'Disetujui level 1 oleh Supervisor.',
        ]);

        $att->refresh();
        // Attendance should still be pending because Level 2 is required!
        $this->assertEquals(AttendanceStatus::Pending, $att->status);

        $level1 = $att->approvals()->where('approval_level', 1)->first();
        $this->assertEquals('approved', $level1->status);
        $this->assertEquals('approver', $level1->approval_source);

        // Level 2 approves (Andi Manager)
        $this->actingAs($this->approverHigher)->post(route('admin.attendances.approve', $att->id), [
            'approval_notes' => 'Disetujui level final oleh Manager.',
        ]);

        $att->refresh();
        // Now attendance is fully Approved!
        $this->assertEquals(AttendanceStatus::Approved, $att->status);
        $this->assertEquals($this->approverHigher->id, $att->approved_by);
        $this->assertEquals('approver', $att->approval_source);
    }

    public function test_reject_requires_reason_and_sets_status_to_rejected(): void
    {
        $att = Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user1->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => '2026-11-17',
            'server_timestamp' => Carbon::parse('2026-11-17 04:30:00'),
            'status' => AttendanceStatus::Pending,
        ]);

        AttendanceApproval::create([
            'attendance_id' => $att->id,
            'approver_id' => $this->approver1->id,
            'approval_level' => 1,
            'status' => 'pending',
        ]);

        // Attempt reject without reason
        $response = $this->actingAs($this->approver1)->post(route('admin.attendances.reject', $att->id), [
            'rejection_reason' => '',
        ]);
        $response->assertSessionHasErrors(['rejection_reason']);

        // Reject with valid reason
        $this->actingAs($this->approver1)->post(route('admin.attendances.reject', $att->id), [
            'rejection_reason' => 'Foto selfie buram dan tidak memuat wajah.',
        ]);

        $att->refresh();
        $this->assertEquals(AttendanceStatus::Rejected, $att->status);
        $this->assertEquals('Foto selfie buram dan tidak memuat wajah.', $att->rejection_reason);
        $this->assertEquals('approver', $att->approval_source);
    }

    public function test_unassigned_user_cannot_approve_other_user_attendance(): void
    {
        // Approver2 is an active approver, but only for User2
        ApprovalProcess::create([
            'user_id' => $this->user2->id,
            'approver_id' => $this->approver2->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $att = Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user1->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => '2026-11-17',
            'server_timestamp' => Carbon::parse('2026-11-17 04:30:00'),
            'status' => AttendanceStatus::Pending,
        ]);

        AttendanceApproval::create([
            'attendance_id' => $att->id,
            'approver_id' => $this->approver1->id, // Assigned to Budi
            'approval_level' => 1,
            'status' => 'pending',
        ]);

        // Approver2 (Rizky) attempts to approve Ahmad's attendance
        $response = $this->actingAs($this->approver2)->post(route('admin.attendances.approve', $att->id), [
            'approval_notes' => 'Mencoba menyetujui tanpa penugasan.',
        ]);

        $response->assertSessionHasErrors(['approver']);
        $att->refresh();
        $this->assertEquals(AttendanceStatus::Pending, $att->status);
    }

    public function test_auto_approval_by_system_approves_expired_pending_attendance(): void
    {
        // Create an attendance from yesterday (strictly expired)
        $yesterday = Carbon::yesterday('Asia/Jakarta')->toDateString();

        $att = Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user1->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => $yesterday,
            'server_timestamp' => Carbon::parse("{$yesterday} 04:30:00"),
            'status' => AttendanceStatus::Pending,
        ]);

        AttendanceApproval::create([
            'attendance_id' => $att->id,
            'approver_id' => $this->approver1->id,
            'approval_level' => 1,
            'status' => 'pending',
        ]);

        // Run artisan command
        $this->artisan('attendance:auto-approve')
            ->expectsOutputToContain('Total attendances auto-approved by system: 1')
            ->assertExitCode(0);

        $att->refresh();
        $this->assertEquals(AttendanceStatus::Approved, $att->status);
        $this->assertEquals('system', $att->approval_source);
        $this->assertStringContainsString('Disetujui otomatis oleh sistem', $att->approval_notes);

        $approval = $att->approvals()->first();
        $this->assertEquals('approved', $approval->status);
        $this->assertEquals('system', $approval->approval_source);

        // Verify idempotency: running again approves 0
        $this->artisan('attendance:auto-approve')
            ->expectsOutputToContain('Total attendances auto-approved by system: 0')
            ->assertExitCode(0);
    }

    public function test_auto_approval_does_not_alter_approved_or_rejected_attendances(): void
    {
        $yesterday = Carbon::yesterday('Asia/Jakarta')->toDateString();

        $approvedAtt = Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user1->id,
            'prayer_type' => PrayerType::Dzuhur,
            'attendance_date' => $yesterday,
            'server_timestamp' => Carbon::parse("{$yesterday} 12:15:00"),
            'status' => AttendanceStatus::Approved,
            'approval_source' => 'approver',
            'approval_notes' => 'Disetujui manual oleh Budi.',
        ]);

        $rejectedAtt = Attendance::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user2->id,
            'prayer_type' => PrayerType::Ashar,
            'attendance_date' => $yesterday,
            'server_timestamp' => Carbon::parse("{$yesterday} 15:30:00"),
            'status' => AttendanceStatus::Rejected,
            'approval_source' => 'approver',
            'rejection_reason' => 'Ditolak secara sah.',
        ]);

        $this->artisan('attendance:auto-approve')
            ->expectsOutputToContain('Total attendances auto-approved by system: 0')
            ->assertExitCode(0);

        $approvedAtt->refresh();
        $rejectedAtt->refresh();

        $this->assertEquals(AttendanceStatus::Approved, $approvedAtt->status);
        $this->assertEquals('approver', $approvedAtt->approval_source);
        $this->assertEquals('Disetujui manual oleh Budi.', $approvedAtt->approval_notes);

        $this->assertEquals(AttendanceStatus::Rejected, $rejectedAtt->status);
        $this->assertEquals('approver', $rejectedAtt->approval_source);
        $this->assertEquals('Ditolak secara sah.', $rejectedAtt->rejection_reason);
    }
}
