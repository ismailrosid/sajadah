<?php

namespace App\Services\Community;

use App\Models\Community;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class CommunityContextService
{
    public const SESSION_KEY = 'active_community_id';

    public function getActiveCommunity(?User $user = null): ?Community
    {
        return null;
    }

    public function setActiveCommunity(Community|int $community): void
    {
        $id = $community instanceof Community ? $community->id : $community;
        Session::put(self::SESSION_KEY, $id);
    }

    public function clearActiveCommunity(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
