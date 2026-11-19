<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Community extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_path',
        'address',
        'reference_city',
        'reference_latitude',
        'reference_longitude',
        'timezone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reference_latitude' => 'float',
            'reference_longitude' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Community $community) {
            if (empty($community->slug)) {
                $community->slug = Str::slug($community->name) . '-' . Str::random(5);
            }
        });

        static::created(function (Community $community) {
            // Auto create default attendance settings for newly created community
            $community->setting()->create([
                'default_radius_meters' => 100,
                'attendance_duration_minutes' => 60,
                'opening_buffer_minutes' => 0,
                'closing_buffer_minutes' => 60,
                'late_tolerance_minutes' => 60,
                'prayer_calc_method' => 'KEMENAG',
                'is_attendance_enabled' => true,
                'require_selfie' => true,
                'require_gps' => true,
            ]);
        });
    }

    public function getLatitude(): float
    {
        if ($this->reference_latitude !== null) {
            return (float) $this->reference_latitude;
        }

        $defaultMosque = $this->defaultMosque ?? $this->mosques()->first();
        if ($defaultMosque && $defaultMosque->latitude) {
            return (float) $defaultMosque->latitude;
        }

        return -6.2088; // Default Jakarta
    }

    public function getLongitude(): float
    {
        if ($this->reference_longitude !== null) {
            return (float) $this->reference_longitude;
        }

        $defaultMosque = $this->defaultMosque ?? $this->mosques()->first();
        if ($defaultMosque && $defaultMosque->longitude) {
            return (float) $defaultMosque->longitude;
        }

        return 106.8456; // Default Jakarta
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommunityMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_members')
            ->withPivot(['id', 'role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function activeUsers(): BelongsToMany
    {
        return $this->users()
            ->wherePivot('status', 'active');
    }

    public function mosques(): HasMany
    {
        return $this->hasMany(Mosque::class);
    }

    public function defaultMosque(): HasOne
    {
        return $this->hasOne(Mosque::class)->where('is_default', true);
    }

    public function setting(): HasOne
    {
        return $this->hasOne(AttendanceSetting::class);
    }

    public function prayerSchedules(): HasMany
    {
        return $this->hasMany(PrayerSchedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CommunityInvitation::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
