<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommunityMemberRole;
use App\Enums\CommunityMemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\User;
use App\Services\Community\CommunityContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunityMemberController extends Controller
{
    public function __construct(
        protected CommunityContextService $contextService
    ) {}

    public function index(Request $request): Response
    {
        $community = $this->contextService->getActiveCommunity($request->user());
        if (!$community) {
            abort(404, 'Komunitas tidak ditemukan.');
        }

        $status = $request->query('status');
        $search = $request->query('search');

        $query = CommunityMember::with('user')
            ->where('community_id', $community->id);

        if ($status && in_array($status, ['active', 'pending', 'rejected', 'inactive'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $members = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn($m) => [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'name' => $m->user->name,
                'email' => $m->user->email,
                'phone' => $m->user->phone,
                'avatar_path' => $m->user->avatar_path,
                'role' => $m->role->value,
                'role_label' => $m->role->label(),
                'status' => $m->status->value,
                'status_label' => $m->status->label(),
                'joined_at' => $m->joined_at ? $m->joined_at->format('d M Y H:i') : null,
                'created_at' => $m->created_at->format('d M Y'),
            ]);

        $counts = [
            'total' => CommunityMember::where('community_id', $community->id)->count(),
            'active' => CommunityMember::where('community_id', $community->id)->where('status', CommunityMemberStatus::Active->value)->count(),
            'pending' => CommunityMember::where('community_id', $community->id)->where('status', CommunityMemberStatus::Pending->value)->count(),
        ];

        return Inertia::render('Admin/Members/Index', [
            'members' => $members,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'counts' => $counts,
        ]);
    }

    public function approve(Request $request, CommunityMember $member): RedirectResponse
    {
        $community = $this->contextService->getActiveCommunity($request->user());
        if ($member->community_id !== $community->id) {
            abort(403);
        }

        $member->update([
            'status' => CommunityMemberStatus::Active,
            'joined_at' => now(),
        ]);

        return back()->with('success', "Keanggotaan {$member->user->name} berhasil disetujui.");
    }

    public function reject(Request $request, CommunityMember $member): RedirectResponse
    {
        $community = $this->contextService->getActiveCommunity($request->user());
        if ($member->community_id !== $community->id) {
            abort(403);
        }

        $member->update([
            'status' => CommunityMemberStatus::Rejected,
        ]);

        return back()->with('success', "Permintaan bergabung {$member->user->name} ditolak.");
    }

    public function updateRole(Request $request, CommunityMember $member): RedirectResponse
    {
        $community = $this->contextService->getActiveCommunity($request->user());
        if ($member->community_id !== $community->id) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:community_admin,member'],
        ]);

        $member->update([
            'role' => $validated['role'],
        ]);

        return back()->with('success', "Peran {$member->user->name} diubah menjadi {$member->role->label()}.");
    }

    public function destroy(Request $request, CommunityMember $member): RedirectResponse
    {
        $community = $this->contextService->getActiveCommunity($request->user());
        if ($member->community_id !== $community->id) {
            abort(403);
        }

        $userName = $member->user->name;
        $member->delete();

        return back()->with('success', "{$userName} berhasil dikeluarkan dari komunitas.");
    }

    public function invite(Request $request): RedirectResponse
    {
        $community = $this->contextService->getActiveCommunity($request->user());
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:community_admin,member'],
        ]);

        // Check if user already exists
        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            $existing = CommunityMember::where('community_id', $community->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                return back()->with('error', 'Pengguna dengan email ini sudah terdaftar di komunitas.');
            }

            CommunityMember::create([
                'community_id' => $community->id,
                'user_id' => $user->id,
                'role' => $validated['role'],
                'status' => CommunityMemberStatus::Active,
                'joined_at' => now(),
            ]);

            return back()->with('success', "{$user->name} berhasil ditambahkan langsung ke komunitas.");
        }

        return back()->with('info', "Undangan dikirim ke {$validated['email']}. Pengguna dapat mendaftar dengan email tersebut.");
    }
}
