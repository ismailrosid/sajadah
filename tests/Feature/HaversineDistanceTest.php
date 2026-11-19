<?php

namespace Tests\Feature;

use App\Services\Attendance\HaversineDistanceCalculator;
use Tests\TestCase;

class HaversineDistanceTest extends TestCase
{
    public function test_calculates_distance_between_two_known_coordinates_accurately(): void
    {
        $calculator = new HaversineDistanceCalculator();

        // Titik 1: Monas Jakarta (-6.175392, 106.827153)
        // Titik 2: Masjid Istiqlal Jakarta (-6.170170, 106.831710)
        // Jarak garis lurus terhitung sekitar 760 - 780 meter
        $distance = $calculator->calculate(-6.175392, 106.827153, -6.170170, 106.831710);

        $this->assertGreaterThan(700, $distance);
        $this->assertLessThan(850, $distance);
    }

    public function test_same_coordinate_yields_zero_meters(): void
    {
        $calculator = new HaversineDistanceCalculator();

        $distance = $calculator->calculate(-6.2088, 106.8456, -6.2088, 106.8456);

        $this->assertEquals(0.0, $distance);
    }
}
