<?php

namespace Tests\Feature;

use App\Services\Prayer\PrayerScheduleCalculationService;
use Carbon\Carbon;
use Tests\TestCase;

class PrayerScheduleCalculationTest extends TestCase
{
    public function test_calculates_chronological_five_daily_prayers(): void
    {
        $service = new PrayerScheduleCalculationService();

        // Jakarta coordinates
        $times = $service->calculate(-6.2088, 106.8456, Carbon::today(), 'KEMENAG', 7.0);

        $this->assertArrayHasKey('fajr', $times);
        $this->assertArrayHasKey('dhuhr', $times);
        $this->assertArrayHasKey('asr', $times);
        $this->assertArrayHasKey('maghrib', $times);
        $this->assertArrayHasKey('isha', $times);

        // Verify chronological order: Fajr < Dhuhr < Asr < Maghrib < Isha
        $this->assertTrue(strtotime($times['fajr']) < strtotime($times['dhuhr']));
        $this->assertTrue(strtotime($times['dhuhr']) < strtotime($times['asr']));
        $this->assertTrue(strtotime($times['asr']) < strtotime($times['maghrib']));
        $this->assertTrue(strtotime($times['maghrib']) < strtotime($times['isha']));
    }
}
