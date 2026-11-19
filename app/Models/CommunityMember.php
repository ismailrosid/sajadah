<?php

namespace App\Models;

use App\Enums\CommunityMemberRole;
use App\Enums\CommunityMemberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'user_id',
        'role',
        'status',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => CommunityMemberRole::class,
            'status' => CommunityMemberStatus::class,
            'joined_at' => 'datetime',
        ];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === CommunityMemberRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === CommunityMemberStatus::Active;
    }
}
