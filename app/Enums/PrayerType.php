<?php

namespace App\Enums;

enum PrayerType: string
{
    case Subuh = 'subuh';
    case Dzuhur = 'dzuhur';
    case Ashar = 'ashar';
    case Maghrib = 'maghrib';
    case Isya = 'isya';

    public function label(): string
    {
        return match ($this) {
            self::Subuh => 'Subuh',
            self::Dzuhur => 'Dzuhur',
            self::Ashar => 'Ashar',
            self::Maghrib => 'Maghrib',
            self::Isya => 'Isya',
        };
    }

    public static function ordered(): array
    {
        return [
            self::Subuh,
            self::Dzuhur,
            self::Ashar,
            self::Maghrib,
            self::Isya,
        ];
    }
}
