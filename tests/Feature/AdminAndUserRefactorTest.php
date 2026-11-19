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

class AdminAndUserRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $userA; // User needing approval
    protected User $userB; // Approver for User A
    protected User $userC; // Regular user without approval duties
    protected ApprovalRole $approvalRole;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        // Admin
        $this->admin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@sholatin.test',
            'global_role' => GlobalRole::Admin,
        ]);

        // User A
        $this->userA = User::factory()->create([
            'name' => 'User Ahmad',
            'email' => 'ahmad@sholatin.test',
            'global_role' => GlobalRole::User,
            'city' => 'Jakarta',
        ]);

        // User B (will be Approver for A)
        $this->userB = User::factory()->create([
            'name' => 'User Budi Approver',
            'email' => 'budi@sholatin.test',
            'global_role' => GlobalRole::User,
            'city' => 'Bandung',
        ]);

        // User C (Regular User without any approval tasks)
        $this->userC = User::factory()->create([
            'name' => 'User Citra Regular',
            'email' => 'citra@sholatin.test',
            'global_role' => GlobalRole::User,
            'city' => 'Surabaya',
        ]);

        // Role & Process
        $this->approvalRole = ApprovalRole::create([
            'name' => 'Approver Sholat',
            'description' => 'Memverifikasi bukti kehadiran',
            'is_active' => true,
        ]);

        // Assign User B as Approver for User A
        ApprovalProcess::create([
            'user_id' => $this->userA->id,
            'approver_id' => $this->userB->id,
            'approval_role_id' => $this->approvalRole->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        // Create personal prayer schedules for today
        foreach ([$this->userA, $this->userB, $this->userC] as $u) {
            PrayerSchedule::create([
                'user_id' => $u->id,
                'schedule_date' => Carbon::today('Asia/Jakarta')->toDateString(),
                'city' => $u->getCity(),
                'fajr' => '04:15:00',
                'dhuhr' => '11:45:00',
                'asr' => '15:05:00',
                'maghrib' => '17:55:00',
                'isha' => '19:08:00',
                'calculation_method' => 'KEMENAG',
            ]);
        }
    }

    /**
     * Skenario 1: User dapat registrasi tanpa Community.
     */
    public function test_1_user_can_register_without_community(): void
    {
        $response = $this->post('/register', [
            'name' => 'Deni Baru',
            'email' => 'deni@sholatin.test',
            'city' => 'Yogyakarta',
            'phone' => '08987654321',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'deni@sholatin.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals(GlobalRole::User, $user->global_role);
        $this->assertFalse($user->isAdmin());
    }

    /**
     * Skenario 2: User dapat login dan menggunakan fitur personal.
     */
    public function test_2_user_can_login_and_access_personal_features(): void
    {
        $response = $this->actingAs($this->userC)->get(route('dashboard'));
        $response->assertOk();

        $historyResponse = $this->actingAs($this->userC)->get(route('member.history.index'));
        $historyResponse->assertOk();

        $profileResponse = $this->actingAs($this->userC)->get(route('profile.edit'));
        $profileResponse->assertOk();
    }

    /**
     * Skenario 3: User dapat melakukan absensi tanpa Community atau Mosque.
     */
    public function test_3_user_can_checkin_personally_without_community_or_mosque(): void
    {
        Carbon::setTestNow(Carbon::today('Asia/Jakarta')->setTime(4, 25, 0));

        $photo = UploadedFile::fake()->image('selfie.jpg', 640, 480);

        $response = $this->actingAs($this->userA)->post(route('member.attendance.store'), [
            'prayer_type' => 'subuh',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'gps_accuracy' => 12.5,
            'location_name' => 'Jalan Menteng Raya',
            'address' => 'Menteng, Jakarta Pusat',
            'photo' => $photo,
        ]);

        $response->assertRedirect(route('member.attendance.create'));
        $response->assertSessionHas('info');

        $attendance = Attendance::where('user_id', $this->userA->id)->first();
        $this->assertNotNull($attendance);
        $this->assertNull($attendance->community_id);
        $this->assertNull($attendance->mosque_id);
        $this->assertEquals(PrayerType::Subuh, $attendance->prayer_type);
        $this->assertEquals(-6.2088, $attendance->latitude);
        $this->assertEquals(106.8456, $attendance->longitude);
        $this->assertNotNull($attendance->photo_path);
        Storage::disk('local')->assertExists($attendance->photo_path);

        Carbon::setTestNow();
    }

    /**
     * Skenario 4: User hanya dapat melihat absensi miliknya sendiri di riwayat.
     */
    public function test_4_user_can_only_view_own_attendance_history(): void
    {
        $attA = Attendance::create([
            'user_id' => $this->userA->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::today()->toDateString(),
            'server_timestamp' => now(),
            'status' => AttendanceStatus::Present,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        $attC = Attendance::create([
            'user_id' => $this->userC->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::today()->toDateString(),
            'server_timestamp' => now(),
            'status' => AttendanceStatus::Present,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        // User A views history
        $response = $this->actingAs($this->userA)->get(route('member.history.index'));
        $response->assertOk();

        // User C cannot view photo of User A
        $photoResponse = $this->actingAs($this->userC)->get(route('member.attendance.photo', $attA->id));
        $photoResponse->assertStatus(403);
    }

    /**
     * Skenario 5: Admin dapat melihat dan mengelola seluruh User.
     */
    public function test_5_admin_can_view_and_manage_all_users(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $response->assertOk();

        // Non-admin cannot access user management
        $forbidden = $this->actingAs($this->userC)->get(route('admin.users.index'));
        $forbidden->assertStatus(403);

        // Admin changes role
        $roleResponse = $this->actingAs($this->admin)->patch(route('admin.users.role', $this->userC->id), [
            'role' => 'admin',
        ]);
        $roleResponse->assertRedirect();
        $this->userC->refresh();
        $this->assertTrue($this->userC->isAdmin());
    }

    /**
     * Skenario 6: Admin dapat mengatur approver untuk User.
     */
    public function test_6_admin_can_configure_approver_for_user(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.approval-config.processes.store'), [
            'user_id' => $this->userC->id,
            'approver_id' => $this->userB->id,
            'approval_role_id' => $this->approvalRole->id,
            'approval_level' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('approval_processes', [
            'user_id' => $this->userC->id,
            'approver_id' => $this->userB->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);
    }

    /**
     * Skenario 7: User yang memiliki tugas approval mendapatkan menu Approval (isApprover() = true).
     */
    public function test_7_user_with_approval_duty_has_approval_access(): void
    {
        // User B is assigned approver for User A
        $this->assertTrue($this->userB->isApprover());

        // User B can access approvals list without admin console mode
        $response = $this->actingAs($this->userB)->get(route('approvals.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Approvals/Index')
            ->where('isAdminMode', false)
            ->where('isApproverOnly', true)
        );

        // Admin accessing admin approvals has isAdminMode = true
        $adminResponse = $this->actingAs($this->admin)->get(route('admin.approvals.index'));
        $adminResponse->assertOk();
        $adminResponse->assertInertia(fn ($page) => $page
            ->component('Admin/Approvals/Index')
            ->where('isAdminMode', true)
            ->where('isApproverOnly', false)
        );
    }

    /**
     * Skenario 8: User yang tidak memiliki tugas approval TIDAK mendapatkan menu Approval (403 Forbidden).
     */
    public function test_8_user_without_approval_duty_cannot_access_approval(): void
    {
        // User C has no approval duty
        $this->assertFalse($this->userC->isApprover());

        $response = $this->actingAs($this->userC)->get(route('approvals.index'));
        $response->assertStatus(403);
    }

    /**
     * Skenario 9: Approver hanya dapat memproses tugas approval miliknya.
     */
    public function test_9_approver_only_processes_assigned_approvals(): void
    {
        Carbon::setTestNow(Carbon::today('Asia/Jakarta')->setTime(4, 25, 0));

        // Attendance of User A (assigned to User B)
        $attA = Attendance::create([
            'user_id' => $this->userA->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'server_timestamp' => now(),
            'status' => AttendanceStatus::Pending,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);
        app(AttendanceApprovalService::class)->initializeApproval($attA);

        // Attendance of User C (not assigned to User B)
        $attC = Attendance::create([
            'user_id' => $this->userC->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'server_timestamp' => now(),
            'status' => AttendanceStatus::Pending,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        // User B can approve A
        $response = $this->actingAs($this->userB)->post(route('approvals.approve', $attA->id), [
            'approval_notes' => 'Disetujui oleh Budi',
        ]);
        $response->assertRedirect();
        $attA->refresh();
        $this->assertTrue($attA->isApproved());

        Carbon::setTestNow();
    }

    /**
     * Skenario 10: User tidak dapat menyetujui absensinya sendiri.
     */
    public function test_10_user_cannot_approve_own_attendance(): void
    {
        // Setup approval process for User B (approver is User C) so attendance requires approval
        ApprovalProcess::create([
            'user_id' => $this->userB->id,
            'approver_id' => $this->userC->id,
            'approval_role_id' => $this->approvalRole->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        // Even if User B is an approver, User B cannot approve their own attendance!
        $attB = Attendance::create([
            'user_id' => $this->userB->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'server_timestamp' => now(),
            'status' => AttendanceStatus::Pending,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);
        app(AttendanceApprovalService::class)->initializeApproval($attB);

        $response = $this->actingAs($this->userB)->post(route('approvals.approve', $attB->id), [
            'approval_notes' => 'Coba menyetujui diri sendiri',
        ]);

        $response->assertSessionHasErrors('approver');
        $attB->refresh();
        $this->assertEquals(AttendanceStatus::Pending, $attB->status);
    }

    /**
     * Skenario 11: Approval grouping per tanggal tetap berjalan.
     */
    public function test_11_approval_grouping_by_date_functions_properly(): void
    {
        Attendance::create([
            'user_id' => $this->userA->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => '2026-11-17',
            'server_timestamp' => Carbon::parse('2026-11-17 04:30:00'),
            'status' => AttendanceStatus::Pending,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        $dailyGroups = app(AttendanceApprovalService::class)->getGroupedDailyApprovals($this->admin);
        $this->assertGreaterThan(0, $dailyGroups->total());
        $firstItem = $dailyGroups->first();
        $this->assertArrayHasKey('attendance_date', $firstItem);
        $this->assertArrayHasKey('total_attendances', $firstItem);
        $this->assertArrayHasKey('pending_count', $firstItem);
    }

    /**
     * Skenario 12: Detail absensi tetap menampilkan selfie, GPS, waktu, dan informasi lainnya.
     */
    public function test_12_attendance_detail_displays_selfie_gps_and_time(): void
    {
        $att = Attendance::create([
            'user_id' => $this->userA->id,
            'prayer_type' => PrayerType::Dzuhur,
            'attendance_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'server_timestamp' => Carbon::today('Asia/Jakarta')->setTime(12, 5, 0),
            'status' => AttendanceStatus::Pending,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'gps_accuracy' => 15.0,
            'request_notes' => 'Masbuq 1 rakaat',
        ]);
        app(AttendanceApprovalService::class)->initializeApproval($att);

        $detail = app(AttendanceApprovalService::class)->getAttendanceVerificationDetail($this->admin, $att);
        $this->assertEquals($att->id, $detail['id']);
        $this->assertEquals('Dzuhur', $detail['attendance_info']['prayer_label']);
        $this->assertEquals(-6.2088, $detail['gps']['latitude']);
        $this->assertEquals(106.8456, $detail['gps']['longitude']);
        $this->assertEquals('Masbuq 1 rakaat', $detail['request_notes']);
    }

    /**
     * Skenario 13: Auto approval oleh sistem tetap berjalan.
     */
    public function test_13_auto_approval_by_system_approves_expired_pending_attendance(): void
    {
        // Attendance yesterday with pending status
        $expiredAtt = Attendance::create([
            'user_id' => $this->userA->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::yesterday('Asia/Jakarta')->toDateString(),
            'server_timestamp' => Carbon::yesterday('Asia/Jakarta')->setTime(4, 30, 0),
            'status' => AttendanceStatus::Pending,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);
        app(AttendanceApprovalService::class)->initializeApproval($expiredAtt);

        $approvedCount = app(AttendanceApprovalService::class)->autoApproveExpiredPendingAttendances();
        $this->assertGreaterThanOrEqual(1, $approvedCount);

        $expiredAtt->refresh();
        $this->assertEquals(AttendanceStatus::Approved, $expiredAtt->status);
        $this->assertEquals('system', $expiredAtt->approval_source);
    }

    /**
     * Skenario 14: Riwayat approval tetap tersimpan (AttendanceApproval & ActivityLog).
     */
    public function test_14_approval_history_is_persisted(): void
    {
        $att = Attendance::create([
            'user_id' => $this->userA->id,
            'prayer_type' => PrayerType::Ashar,
            'attendance_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'server_timestamp' => Carbon::today('Asia/Jakarta')->setTime(15, 10, 0),
            'status' => AttendanceStatus::Pending,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);
        app(AttendanceApprovalService::class)->initializeApproval($att);

        app(AttendanceApprovalService::class)->approve($att, $this->userB, 'Catatan disetujui');

        $this->assertDatabaseHas('attendance_approvals', [
            'attendance_id' => $att->id,
            'approver_id' => $this->userB->id,
            'status' => 'approved',
            'note' => 'Catatan disetujui',
        ]);
    }

    /**
     * Skenario 15: Tidak ada lagi dependency Community atau Mosque pada proses utama aplikasi.
     */
    public function test_15_no_community_or_mosque_dependency_in_core_flow(): void
    {
        // Ensure no communities or mosques are required to register, login, check-in, or approve
        $userNew = User::factory()->create([
            'email' => 'soleuser@sholatin.test',
            'global_role' => GlobalRole::User,
        ]);

        PrayerSchedule::create([
            'user_id' => $userNew->id,
            'schedule_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'city' => $userNew->getCity(),
            'fajr' => '04:15:00',
            'dhuhr' => '11:45:00',
            'asr' => '15:05:00',
            'maghrib' => '17:55:00',
            'isha' => '19:08:00',
            'calculation_method' => 'KEMENAG',
        ]);

        Carbon::setTestNow(Carbon::today('Asia/Jakarta')->setTime(11, 50, 0));

        $photo = UploadedFile::fake()->image('selfie.jpg', 640, 480);
        $res = $this->actingAs($userNew)->post(route('member.attendance.store'), [
            'prayer_type' => 'dzuhur',
            'latitude' => -7.250445,
            'longitude' => 112.768845,
            'gps_accuracy' => 8.0,
            'photo' => $photo,
        ]);

        $res->assertRedirect();
        $att = Attendance::where('user_id', $userNew->id)->first();
        $this->assertNotNull($att);
        $this->assertNull($att->community_id);
        $this->assertNull($att->mosque_id);

        Carbon::setTestNow();
    }

    /**
     * Skenario 16: Approver dapat melihat riwayat sholat anakannya dan status 'Tidak Sholat'.
     */
    public function test_16_approver_can_view_subordinate_history_and_missed_prayer_status(): void
    {
        // Buat absensi User A dengan status Absent (Tidak Sholat)
        $attMissed = Attendance::create([
            'user_id' => $this->userA->id,
            'prayer_type' => PrayerType::Subuh,
            'attendance_date' => Carbon::yesterday()->toDateString(),
            'server_timestamp' => Carbon::yesterday()->setTime(4, 15, 0),
            'status' => AttendanceStatus::Absent,
        ]);

        // User B (approver untuk User A) melihat riwayat User A
        $resApprover = $this->actingAs($this->userB)->get(route('member.history.index', [
            'user_id' => $this->userA->id,
            'status' => 'absent',
        ]));
        $resApprover->assertOk();
        $resApprover->assertInertia(fn ($page) => $page
            ->component('Member/History/Index')
            ->where('isViewingSubordinate', true)
            ->where('targetUser.id', $this->userA->id)
            ->has('subordinates', 1)
        );

        // User C (bukan approver User A) mencoba melihat riwayat User A -> fallback ke diri sendiri
        $resUnauthorized = $this->actingAs($this->userC)->get(route('member.history.index', [
            'user_id' => $this->userA->id,
        ]));
        $resUnauthorized->assertOk();
        $resUnauthorized->assertInertia(fn ($page) => $page
            ->where('isViewingSubordinate', false)
            ->where('targetUser.id', $this->userC->id)
        );
    }

    /**
     * Skenario 17: User yang tidak memiliki approver/atasan langsung auto-approved saat absensi.
     */
    public function test_17_user_without_approver_is_auto_approved(): void
    {
        Carbon::setTestNow(Carbon::today('Asia/Jakarta')->setTime(4, 25, 0));

        // User C tidak memiliki konfigurasi approver
        $this->assertDatabaseMissing('approval_processes', [
            'user_id' => $this->userC->id,
            'is_active' => true,
        ]);

        $photo = UploadedFile::fake()->image('selfie.jpg', 640, 480);

        $response = $this->actingAs($this->userC)->post(route('member.attendance.store'), [
            'prayer_type' => 'subuh',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'gps_accuracy' => 10.0,
            'photo' => $photo,
        ]);

        $response->assertRedirect(route('member.attendance.create'));

        $attendance = Attendance::where('user_id', $this->userC->id)
            ->where('prayer_type', PrayerType::Subuh)
            ->first();

        $this->assertNotNull($attendance);
        // Status langsung Present (Disetujui), bukan pending
        $this->assertEquals(AttendanceStatus::Present, $attendance->status);
        $this->assertTrue($attendance->isApproved());

        // Tidak masuk ke antrean pending approval
        $this->assertDatabaseMissing('attendance_approvals', [
            'attendance_id' => $attendance->id,
            'status' => 'pending',
        ]);

        // Catatan approval tersimpan sebagai approved otomatis
        $this->assertDatabaseHas('attendance_approvals', [
            'attendance_id' => $attendance->id,
            'status' => 'approved',
            'approval_source' => 'auto_approved',
        ]);

        Carbon::setTestNow();
    }
}
