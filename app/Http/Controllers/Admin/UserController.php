<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GlobalRole;
use App\Http\Controllers\Controller;
use App\Models\ApprovalProcess;
use App\Models\ApprovalRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $role = $request->query('role');
        $approverFilter = $request->query('approver');

        $query = User::with(['approvalProcesses.approver', 'approvalProcesses.approvalRole', 'assignedApprovalProcesses']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($role && in_array($role, ['admin', 'user'])) {
            if ($role === 'admin') {
                $query->whereIn('global_role', ['admin', 'super_admin']);
            } else {
                $query->where('global_role', 'user');
            }
        }

        if ($approverFilter === 'yes') {
            $query->whereHas('assignedApprovalProcesses', function ($q) {
                $q->where('is_active', true);
            });
        } elseif ($approverFilter === 'no') {
            $query->whereDoesntHave('assignedApprovalProcesses', function ($q) {
                $q->where('is_active', true);
            });
        }

        $users = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'city' => $u->getCity(),
                'role' => $u->isAdmin() ? 'admin' : 'user',
                'role_label' => $u->isAdmin() ? 'Admin' : 'User',
                'is_approver' => $u->isApprover(),
                'assigned_approvers' => $u->approvalProcesses->map(fn($p) => [
                    'id' => $p->id,
                    'approver_name' => $p->approver?->name,
                    'role_name' => $p->approvalRole?->name,
                    'level' => $p->approval_level,
                    'is_active' => $p->is_active,
                ]),
                'approver_for_count' => $u->assignedApprovalProcesses->where('is_active', true)->count(),
                'created_at' => $u->created_at->format('d M Y'),
            ]);

        $counts = [
            'total' => User::count(),
            'admins' => User::whereIn('global_role', ['admin', 'super_admin'])->count(),
            'users' => User::where('global_role', 'user')->count(),
            'approvers' => User::whereHas('assignedApprovalProcesses', fn($q) => $q->where('is_active', true))->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'approver' => $approverFilter,
            ],
            'counts' => $counts,
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,user'],
        ]);

        // Prevent self-demotion if current user is demoting themselves
        if ($user->id === $request->user()->id && $validated['role'] === 'user') {
            return back()->with('error', 'Anda tidak dapat menurunkan peran akun Anda sendiri.');
        }

        $user->update([
            'global_role' => $validated['role'] === 'admin' ? GlobalRole::Admin : GlobalRole::User,
        ]);

        return back()->with('success', "Peran pengguna {$user->name} berhasil diubah menjadi " . ucfirst($validated['role']) . ".");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return back()->with('success', "Pengguna {$user->name} berhasil dihapus.");
    }
}
