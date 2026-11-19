<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\Approval\AttendanceApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceApprovalController extends Controller
{
    public function __construct(
        protected AttendanceApprovalService $approvalService
    ) {}

    /**
     * Halaman 1 – Approval Attendance (Daily Grouping)
     * Groups attendances by date. ONE ROW PER DATE on main table!
     */
    public function index(Request $request): Response
    {
        $viewer = $request->user();
        $isAdminMode = $request->routeIs('admin.*') && $viewer->isAdmin();

        $filters = [
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ];

        $dailyGroups = $this->approvalService->getGroupedDailyApprovals($viewer, $filters);

        return Inertia::render('Admin/Approvals/Index', [
            'dailyGroups' => $dailyGroups,
            'filters' => $filters,
            'isApproverOnly' => !$viewer->isAdmin(),
            'isAdminMode' => $isAdminMode,
        ]);
    }

    /**
     * Halaman 2 – Daily Approval Detail
     * Displays all attendances for a specific date within viewer's scope.
     */
    public function dailyDetail(Request $request, string $date): Response
    {
        $viewer = $request->user();
        $isAdminMode = $request->routeIs('admin.*') && $viewer->isAdmin();

        $filters = [
            'prayer_type' => $request->query('prayer_type'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ];

        $attendances = $this->approvalService->getDailyDetails($viewer, $date, $filters);

        return Inertia::render('Admin/Approvals/DailyDetail', [
            'date' => $date,
            'attendances' => $attendances,
            'filters' => $filters,
            'isAdminMode' => $isAdminMode,
        ]);
    }

    /**
     * Halaman 3 – Attendance Verification Detail
     * Detailed verification evidence: User info, Selfie photo with zoom, GPS map, Prayer times, Approval history.
     */
    public function show(Request $request, Attendance $attendance): Response
    {
        $viewer = $request->user();
        $isAdminMode = $request->routeIs('admin.*') && $viewer->isAdmin();

        $verification = $this->approvalService->getAttendanceVerificationDetail($viewer, $attendance);

        return Inertia::render('Admin/Approvals/Show', [
            'attendance' => $verification,
            'isAdminMode' => $isAdminMode,
        ]);
    }

    /**
     * Action: Approve Attendance
     */
    public function approve(Request $request, Attendance $attendance): RedirectResponse
    {
        $viewer = $request->user();
        $notes = $request->input('approval_notes', $request->input('notes'));

        $this->approvalService->approve($attendance, $viewer, $notes);

        return redirect()->back()->with('success', "Absensi Sholat {$attendance->prayer_type->label()} dari {$attendance->user->name} berhasil disetujui.");
    }

    /**
     * Action: Reject Attendance (Reason is required)
     */
    public function reject(Request $request, Attendance $attendance): RedirectResponse
    {
        $viewer = $request->user();

        $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $request->input('rejection_reason', $request->input('reason'));

        if (empty(trim((string)$reason))) {
            return redirect()->back()->withErrors(['rejection_reason' => 'Alasan penolakan absensi wajib diisi.']);
        }

        $this->approvalService->reject($attendance, $viewer, $reason);

        return redirect()->back()->with('success', "Absensi Sholat {$attendance->prayer_type->label()} dari {$attendance->user->name} ditolak.");
    }
}
