<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprover
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if (!$user->isApprover()) {
            abort(403, 'Akses terbatas untuk Approver atau Administrator.');
        }

        return $next($request);
    }
}
