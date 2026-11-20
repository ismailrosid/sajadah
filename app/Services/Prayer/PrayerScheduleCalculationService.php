<?php

namespace App\Services\Prayer;

use App\Models\Community;
use App\Models\Mosque;
use App\Models\PrayerSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PrayerScheduleCalculationService
{
    /**
     * Map method string to Aladhan API method IDs.
     */
    protected const ALADHAN_METHOD_MAP = [
        'KEMENAG' => 20, // Kementerian Agama Republik Indonesia
        'MWL' => 3,     // Muslim World League
        'ISNA' => 2,    // Islamic Society of North America
        'EGYPT' => 5,   // Egyptian General Authority of Survey
        'MAKKAH' => 4,  // Umm Al-Qura University, Makkah
    ];

    /**
     * Get prayer schedule for a specific date and mosque/community.
     * Ensures persistence in database and caching so prayer times do not drift on same day.
     */
    /**
     * Get prayer schedule for a specific date and context (User, Community, or coordinates).
     * Ensures persistence in database and caching so prayer times do not drift on same day.
     */
    public function getOrFetchSchedule(
        Community|User|null $context = null,
        ?Mosque $mosque = null,
        Carbon|string|null $date = null
    ): ?PrayerSchedule {
        $date = $date ?? Carbon::today();
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateStr = $carbonDate->toDateString();

        $mosqueId = $mosque?->id;
        $userId = $context instanceof User ? $context->id : null;
        $communityId = $context instanceof Community ? $context->id : null;

        // 1. Check existing record in database
        if ($userId) {
            $existing = PrayerSchedule::where('user_id', $userId)
                ->whereDate('schedule_date', $dateStr)
                ->first();

            if ($existing) {
                return $existing;
            }
        } elseif ($mosqueId && Schema::hasColumn('prayer_schedules', 'mosque_id')) {
            $existing = PrayerSchedule::where('mosque_id', $mosqueId)
                ->whereDate('schedule_date', $dateStr)
                ->first();

            if ($existing) {
                return $existing;
            }
        } elseif ($communityId && Schema::hasColumn('prayer_schedules', 'community_id')) {
            $existing = PrayerSchedule::where('community_id', $communityId)
                ->whereDate('schedule_date', $dateStr)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        // 2. Determine Coordinates, Calculation Method, and Timezone
        if ($context instanceof User) {
            $latitude = $context->getLatitude();
            $longitude = $context->getLongitude();
            $method = $context->getPrayerCalcMethod();
            $timezone = $context->getTimezone();
            $city = $context->getCity();
        } elseif ($context instanceof Community) {
            $latitude = $mosque ? (float) $mosque->latitude : $context->getLatitude();
            $longitude = $mosque ? (float) $mosque->longitude : $context->getLongitude();
            $method = $context->setting?->prayer_calc_method ?? 'KEMENAG';
            $timezone = $context->timezone ?? 'Asia/Jakarta';
            $city = $context->reference_city;
        } else {
            $latitude = -6.20880000;
            $longitude = 106.84560000;
            $method = 'KEMENAG';
            $timezone = 'Asia/Jakarta';
            $city = 'Jakarta';
        }

        // 3. Try Aladhan API first with safe fallback
        $times = $this->fetchFromAladhanApi($latitude, $longitude, $carbonDate, $method);

        // If API fails or is offline, use astronomical calculation fallback
        if (!$times) {
            $timezoneOffset = $this->getTimezoneOffsetHours($timezone, $carbonDate);
            $times = $this->calculateAstronomical(
                $latitude,
                $longitude,
                $carbonDate,
                $method,
                $timezoneOffset
            );
        }

        if (!$times) {
            return null;
        }

        // 4. Save to database for persistence and historical consistency
        $query = PrayerSchedule::whereDate('schedule_date', $dateStr);
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($mosqueId && Schema::hasColumn('prayer_schedules', 'mosque_id')) {
            $query->where('mosque_id', $mosqueId);
        } elseif ($communityId && Schema::hasColumn('prayer_schedules', 'community_id')) {
            $query->where('community_id', $communityId);
        }
        $existing = $query->first();

        if ($existing) {
            $existing->update([
                'fajr' => $times['fajr'],
                'dhuhr' => $times['dhuhr'],
                'asr' => $times['asr'],
                'maghrib' => $times['maghrib'],
                'isha' => $times['isha'],
                'calculation_method' => $method,
                'city' => $city,
            ]);
            return $existing;
        }

        $scheduleData = [
            'user_id' => $userId,
            'city' => $city,
            'schedule_date' => $dateStr,
            'fajr' => $times['fajr'],
            'dhuhr' => $times['dhuhr'],
            'asr' => $times['asr'],
            'maghrib' => $times['maghrib'],
            'isha' => $times['isha'],
            'calculation_method' => $method,
        ];

        if ($mosqueId && Schema::hasColumn('prayer_schedules', 'mosque_id')) {
            $scheduleData['mosque_id'] = $mosqueId;
        }
        if ($communityId && Schema::hasColumn('prayer_schedules', 'community_id')) {
            $scheduleData['community_id'] = $communityId;
        }

        return PrayerSchedule::create($scheduleData);
    }

    /**
     * Get or fetch schedule specifically for a User.
     */
    public function getScheduleForUser(User $user, Carbon|string|null $date = null): ?PrayerSchedule
    {
        return $this->getOrFetchSchedule($user, null, $date);
    }

    /**
     * Fetch prayer times from Aladhan API with timeout.
     */
    protected function fetchFromAladhanApi(
        float $latitude,
        float $longitude,
        Carbon $date,
        string $method
    ): ?array {
        $methodId = self::ALADHAN_METHOD_MAP[strtoupper($method)] ?? 20;

        try {
            $response = Http::timeout(2.5)->get("https://api.aladhan.com/v1/timings/" . $date->timestamp, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'method' => $methodId,
            ]);

            if ($response->successful()) {
                $timings = $response->json('data.timings');
                if ($timings && isset($timings['Fajr'], $timings['Dhuhr'], $timings['Asr'], $timings['Maghrib'], $timings['Isha'])) {
                    return [
                        'fajr' => $this->sanitizeTime($timings['Fajr']),
                        'dhuhr' => $this->sanitizeTime($timings['Dhuhr']),
                        'asr' => $this->sanitizeTime($timings['Asr']),
                        'maghrib' => $this->sanitizeTime($timings['Maghrib']),
                        'isha' => $this->sanitizeTime($timings['Isha']),
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::info("Aladhan API unreachable or timed out, falling back to astronomical calculation: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Sanitize time string from API (e.g., "04:22 (WIB)" -> "04:22:00").
     */
    protected function sanitizeTime(string $timeStr): string
    {
        $clean = preg_replace('/[^0-9:]/', '', $timeStr);
        $parts = explode(':', $clean);

        $hours = isset($parts[0]) ? str_pad($parts[0], 2, '0', STR_PAD_LEFT) : '00';
        $minutes = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_LEFT) : '00';
        $seconds = isset($parts[2]) ? str_pad($parts[2], 2, '0', STR_PAD_LEFT) : '00';

        return "{$hours}:{$minutes}:{$seconds}";
    }

    /**
     * Calculate 5 daily prayer times using high-accuracy astronomical formulas (Equation of Time).
     */
    public function calculate(
        float $latitude,
        float $longitude,
        Carbon|string $date,
        string $method = 'KEMENAG',
        float $timezoneOffset = 7.0
    ): array {
        return $this->calculateAstronomical($latitude, $longitude, $date, $method, $timezoneOffset);
    }

    protected function calculateAstronomical(
        float $latitude,
        float $longitude,
        Carbon|string $date,
        string $method = 'KEMENAG',
        float $timezoneOffset = 7.0
    ): array {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dayOfYear = $carbonDate->dayOfYear;

        // Solar Declination & Equation of Time approximation
        $b = 2 * M_PI * ($dayOfYear - 81) / 365;
        $equationOfTime = 9.87 * sin(2 * $b) - 7.53 * cos($b) - 1.5 * sin($b); // minutes
        $solarDeclination = deg2rad(23.45 * sin(deg2rad(360 / 365 * ($dayOfYear - 81)))); // radians
        $latRad = deg2rad($latitude);

        // Solar Noon (Dhuhr) in fractional hours
        $dhuhrHours = 12 + $timezoneOffset - ($longitude / 15.0) - ($equationOfTime / 60.0);
        $dhuhrHours += (2.0 / 60.0); // Safety buffer for adzan

        [$fajrAngle, $ishaAngle] = match (strtoupper($method)) {
            'MWL' => [18.0, 17.0],
            'EGYPT' => [19.5, 17.5],
            'ISNA' => [15.0, 15.0],
            default => [20.0, 18.0], // Standar Kemenag RI
        };

        $calcHourAngle = function (float $angle) use ($latRad, $solarDeclination): float {
            $sinAlt = sin(deg2rad($angle));
            $cosH = ($sinAlt - sin($latRad) * sin($solarDeclination)) / (cos($latRad) * cos($solarDeclination));
            $cosH = max(-1.0, min(1.0, $cosH));
            return rad2deg(acos($cosH)) / 15.0;
        };

        $t = tan(abs($latRad - $solarDeclination));
        $asrAlt = rad2deg(atan(1.0 / (1.0 + $t)));
        $asrHourAngle = $calcHourAngle($asrAlt);

        $maghribHourAngle = $calcHourAngle(-0.833);
        $fajrHourAngle = $calcHourAngle(-$fajrAngle);
        $ishaHourAngle = $calcHourAngle(-$ishaAngle);

        $fajr = $dhuhrHours - $fajrHourAngle;
        $asr = $dhuhrHours + $asrHourAngle;
        $maghrib = $dhuhrHours + $maghribHourAngle;
        $isha = $dhuhrHours + $ishaHourAngle;

        return [
            'fajr' => $this->formatTime($fajr),
            'dhuhr' => $this->formatTime($dhuhrHours),
            'asr' => $this->formatTime($asr),
            'maghrib' => $this->formatTime($maghrib),
            'isha' => $this->formatTime($isha),
        ];
    }

    protected function formatTime(float $fractionalHours): string
    {
        $fractionalHours = fmod($fractionalHours + 24.0, 24.0);
        $hours = floor($fractionalHours);
        $minutes = round(($fractionalHours - $hours) * 60.0);

        if ($minutes >= 60) {
            $hours = ($hours + 1) % 24;
            $minutes = 0;
        }

        return sprintf('%02d:%02d:00', $hours, $minutes);
    }

    protected function getTimezoneOffsetHours(string $timezone, Carbon $date): float
    {
        try {
            $tz = new \DateTimeZone($timezone);
            $dt = new \DateTime($date->toDateString(), $tz);
            return $tz->getOffset($dt) / 3600.0;
        } catch (Throwable $e) {
            return 7.0; // Default WIB
        }
    }

    /**
     * Evaluate automatic attendance window for each prayer.
     * Window opens when prayer time begins, closes after $durationMinutes.
     */
    public function evaluateWindows(
        PrayerSchedule $schedule,
        int $durationMinutes = 60,
        ?Carbon $serverNow = null,
        string $timezone = 'Asia/Jakarta'
    ): array {
        $now = $serverNow ?? Carbon::now($timezone);
        $dateStr = $schedule->schedule_date->toDateString();

        $prayers = [
            'subuh' => ['label' => 'Subuh', 'time' => $schedule->fajr],
            'dzuhur' => ['label' => 'Dzuhur', 'time' => $schedule->dhuhr],
            'ashar' => ['label' => 'Ashar', 'time' => $schedule->asr],
            'maghrib' => ['label' => 'Maghrib', 'time' => $schedule->maghrib],
            'isya' => ['label' => 'Isya', 'time' => $schedule->isha],
        ];

        $nextPrayerMap = [
            'subuh' => 'dzuhur',
            'dzuhur' => 'ashar',
            'ashar' => 'maghrib',
            'maghrib' => 'isya',
            'isya' => 'subuh',
        ];

        $results = [];
        $activeOpenWindow = null;
        $nextUpcomingPrayer = null;

        foreach ($prayers as $key => $info) {
            $timeStr = substr($info['time'], 0, 5);
            $windowStart = Carbon::parse("{$dateStr} {$info['time']}", $timezone);
            $windowEnd = $windowStart->copy()->addMinutes($durationMinutes);

            $nextKey = $nextPrayerMap[$key];
            $nextTime = $prayers[$nextKey]['time'];
            $nextPrayerStart = Carbon::parse("{$dateStr} {$nextTime}", $timezone);
            if ($key === 'isya') {
                $nextPrayerStart->addDay();
            }

            $isStarted = $now->gte($windowStart);
            $isOpen = $now->gte($windowStart) && $now->lte($windowEnd);
            $isClosed = $now->gt($windowEnd);
            $isNextStarted = $now->gte($nextPrayerStart);

            // Sholat hari ini yang sudah lewat jendela normal selalu bisa diajukan susulan
            $canLate = $isClosed;
            $isMissed = false;

            $remainingWindowSeconds = $isOpen ? max(0, (int) round($now->diffInSeconds($windowEnd, false))) : 0;
            $remainingUntilPrayerSeconds = !$isStarted ? max(0, (int) round($now->diffInSeconds($windowStart, false))) : 0;

            if ($isOpen) {
                $status = 'open'; // Sedang Terbuka
            } elseif (!$isStarted) {
                $status = 'upcoming'; // Belum Masuk
            } else {
                $status = 'late_available'; // Periode Normal Berakhir -> Bisa Susulan
            }

            $prayerData = [
                'type' => $key,
                'label' => $info['label'],
                'time' => $timeStr,
                'window_start' => $windowStart->format('H:i'),
                'window_end' => $windowEnd->format('H:i'),
                'window_start_iso' => $windowStart->toIso8601String(),
                'window_end_iso' => $windowEnd->toIso8601String(),
                'next_prayer_start' => $nextPrayerStart->format('H:i'),
                'is_started' => $isStarted,
                'is_open' => $isOpen,
                'is_closed' => $isClosed,
                'can_late' => $canLate,
                'is_missed' => $isMissed,
                'status' => $status,
                'remaining_window_seconds' => $remainingWindowSeconds,
                'remaining_until_prayer_seconds' => $remainingUntilPrayerSeconds,
            ];

            if ($isOpen && !$activeOpenWindow) {
                $activeOpenWindow = $prayerData;
            }

            if (!$isStarted && !$nextUpcomingPrayer) {
                $nextUpcomingPrayer = $prayerData;
            }

            $results[$key] = $prayerData;
        }

        return [
            'prayers' => $results,
            'active_open_window' => $activeOpenWindow,
            'next_upcoming_prayer' => $nextUpcomingPrayer,
            'attendance_duration_minutes' => $durationMinutes,
            'timezone' => $timezone,
        ];
    }
}
