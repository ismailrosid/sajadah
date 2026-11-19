<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city',
        'community_id',
        'mosque_id',
        'schedule_date',
        'fajr',
        'dhuhr',
        'asr',
        'maghrib',
        'isha',
        'calculation_method',
    ];

    protected function casts(): array
    {
        return [
            'schedule_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    public function getTimeFor(string $prayerType): ?string
    {
        return match (strtolower($prayerType)) {
            'subuh', 'fajr' => $this->fajr,
            'dzuhur', 'dhuhr' => $this->dhuhr,
            'ashar', 'asr' => $this->asr,
            'maghrib' => $this->maghrib,
            'isya', 'isha' => $this->isha,
            default => null,
        };
    }
}
