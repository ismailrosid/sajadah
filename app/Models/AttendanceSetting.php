<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'default_radius_meters',
        'attendance_duration_minutes',
        'opening_buffer_minutes',
        'closing_buffer_minutes',
        'late_tolerance_minutes',
        'prayer_calc_method',
        'is_attendance_enabled',
        'require_selfie',
        'require_gps',
        'auto_approve_ontime',
        'auto_approve_after_hours',
    ];

    protected function casts(): array
    {
        return [
            'default_radius_meters' => 'integer',
            'attendance_duration_minutes' => 'integer',
            'opening_buffer_minutes' => 'integer',
            'closing_buffer_minutes' => 'integer',
            'late_tolerance_minutes' => 'integer',
            'is_attendance_enabled' => 'boolean',
            'require_selfie' => 'boolean',
            'require_gps' => 'boolean',
            'auto_approve_ontime' => 'boolean',
            'auto_approve_after_hours' => 'integer',
        ];
    }

    public static function getGlobalSetting(): self
    {
        return static::first() ?? static::create([
            'default_radius_meters' => 100,
            'attendance_duration_minutes' => 60,
            'opening_buffer_minutes' => 30,
            'closing_buffer_minutes' => 45,
            'late_tolerance_minutes' => 15,
            'prayer_calc_method' => 'KEMENAG',
            'is_attendance_enabled' => true,
            'require_selfie' => true,
            'require_gps' => true,
            'auto_approve_ontime' => true,
            'auto_approve_after_hours' => 24,
        ]);
    }
}
