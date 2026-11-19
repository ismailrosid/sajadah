<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Services\Community\CommunityContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommunitySwitcherController extends Controller
{
    public function __construct(
        protected CommunityContextService $contextService
    ) {}

    public function switch(Request $request, Community $community): RedirectResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->isCommunityMember($community)) {
            return back()->with('error', 'Anda tidak memiliki akses ke komunitas ini.');
        }

        $this->contextService->setActiveCommunity($community);

        return back()->with('success', "Beralih ke komunitas {$community->name}");
    }
}
