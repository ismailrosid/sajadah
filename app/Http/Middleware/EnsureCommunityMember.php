<?php

namespace App\Http\Middleware;

use App\Models\Community;
use App\Services\Community\CommunityContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommunityMember
{
    public function __construct(
        protected CommunityContextService $contextService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $community = $request->route('community');
        if (!$community) {
            $community = $this->contextService->getActiveCommunity($user);
        } elseif (is_string($community) || is_numeric($community)) {
            $community = Community::where('id', $community)->orWhere('slug', $community)->first();
        }

        if (!$community || !$user->isCommunityMember($community)) {
            abort(403, 'Anda bukan anggota aktif di komunitas ini.');
        }

        return $next($request);
    }
}
