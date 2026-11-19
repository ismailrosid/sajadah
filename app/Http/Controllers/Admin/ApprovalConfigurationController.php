<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalProcess;
use App\Models\ApprovalRole;
use App\Models\User;
use App\Services\Approval\AttendanceApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalConfigurationController extends Controller
{
    public function __construct(
        protected AttendanceApprovalService $approvalService
    ) {}

    /**
     * Halaman 4 – Approval Configuration
     */
    public function index(Request $request): Response
    {
        $processes = ApprovalProcess::with(['user', 'approver'])
            ->orderBy('user_id')
            ->orderBy('approval_level')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'user_id' => $p->user_id,
                'user_name' => $p->user?->name ?? 'User',
                'user_email' => $p->user?->email,
                'approver_id' => $p->approver_id,
                'approver_name' => $p->approver?->name ?? 'Approver',
                'approver_email' => $p->approver?->email,
                'approval_level' => $p->approval_level,
                'is_active' => $p->is_active,
                'created_at' => $p->created_at->format('d M Y'),
            ]);

        // Users available for assignment
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Approvals/Configuration', [
            'processes' => $processes,
            'users' => $users,
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:approval_roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        ApprovalRole::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', "Approval Role '{$validated['name']}' berhasil ditambahkan.");
    }

    public function updateRole(Request $request, ApprovalRole $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:approval_roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role->update($validated);

        return redirect()->back()->with('success', "Approval Role '{$role->name}' berhasil diperbarui.");
    }

    public function destroyRole(ApprovalRole $role): RedirectResponse
    {
        // If processes use this role, prevent deletion or dissociate safely
        $role->delete();

        return redirect()->back()->with('success', 'Approval Role berhasil dihapus.');
    }

    public function storeProcess(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'approver_id' => ['required', 'exists:users,id', 'different:user_id'],
            'approval_level' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $userId = (int) $validated['user_id'];
        $approverId = (int) $validated['approver_id'];
        $level = (int) $validated['approval_level'];

        // 1. Validation: Prevent duplicate level for same user
        $duplicate = ApprovalProcess::where('user_id', $userId)
            ->where('approval_level', $level)
            ->where('is_active', true)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'approval_level' => "Tingkat approval level {$level} untuk user tersebut sudah ada yang aktif.",
            ]);
        }

        // 2. Validation: Prevent Circular Approval
        if ($this->approvalService->wouldCreateCircularApproval($userId, $approverId)) {
            throw ValidationException::withMessages([
                'approver_id' => 'Penugasan ditolak: terdeteksi alur persetujuan berputar (circular approval).',
            ]);
        }

        ApprovalProcess::create([
            'user_id' => $userId,
            'approver_id' => $approverId,
            'approval_role_id' => null,
            'approval_level' => $level,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Alur approval berhasil dikonfigurasi.');
    }

    public function updateProcess(Request $request, ApprovalProcess $process): RedirectResponse
    {
        $validated = $request->validate([
            'approver_id' => ['required', 'exists:users,id', 'different:user_id'],
            'approval_level' => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['required', 'boolean'],
        ]);

        $userId = $process->user_id;
        $approverId = (int) $validated['approver_id'];
        $level = (int) $validated['approval_level'];

        if ($validated['is_active']) {
            $duplicate = ApprovalProcess::where('user_id', $userId)
                ->where('approval_level', $level)
                ->where('id', '!=', $process->id)
                ->where('is_active', true)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'approval_level' => "Tingkat approval level {$level} untuk user tersebut sudah aktif pada konfigurasi lain.",
                ]);
            }

            if ($this->approvalService->wouldCreateCircularApproval($userId, $approverId)) {
                throw ValidationException::withMessages([
                    'approver_id' => 'Penugasan ditolak: terdeteksi alur persetujuan berputar (circular approval).',
                ]);
            }
        }

        $process->update([
            'approver_id' => $approverId,
            'approval_role_id' => null,
            'approval_level' => $level,
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->back()->with('success', 'Konfigurasi alur approval berhasil diperbarui.');
    }

    public function destroyProcess(ApprovalProcess $process): RedirectResponse
    {
        $process->delete();

        return redirect()->back()->with('success', 'Konfigurasi approval berhasil dihapus.');
    }
}
