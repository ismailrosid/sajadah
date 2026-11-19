<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar_path' => $user->avatar_path,
                    'global_role' => $user->global_role?->value ?? 'user',
                    'city' => $user->getCity(),
                    'timezone' => $user->getTimezone(),
                ] : null,
                'isAdmin' => $user?->isAdmin() ?? false,
                'isApprover' => $user?->isApprover() ?? false,
                'isSuperAdmin' => $user?->isAdmin() ?? false,
                'isCommunityAdmin' => $user?->isAdmin() ?? false,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
