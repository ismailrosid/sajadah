<?php

namespace App\Http\Middleware;

use App\Services\Community\CommunityContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveCommunityContext
{
    public function __construct(
        protected CommunityContextService $contextService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $activeCommunity = $this->contextService->getActiveCommunity($request->user());
            // Attach active community to request attributes for easy controller access
            $request->attributes->set('activeCommunity', $activeCommunity);
        }

        return $next($request);
    }
}
