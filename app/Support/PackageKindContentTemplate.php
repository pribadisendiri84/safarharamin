<?php

namespace App\Support;

use App\Models\City;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\PackageKind;
use Carbon\Carbon;

class PackageKindContentTemplate
{
    /** @var array<string, string> */
    public const PLACEHOLDERS = [
        'package_kind' => 'Tipe paket (Arafah, Mina, Muzdalifah)',
        'type' => 'Jenis paket lengkap (Umroh Reguler, …)',
        'type_short' => 'Jenis paket singkat (Umroh, Umroh Plus, Haji Plus)',
        'title' => 'Judul paket',
        'duration_days' => 'Durasi (angka hari)',
        'hari' => 'Sama dengan {duration_days}',
        'airline' => 'Maskapai',
        'maskapai' => 'Sama dengan {airline}',
        'hotel_makkah' => 'Hotel Makkah',
        'hotel_madinah' => 'Hotel Madinah',
        'hotel_stars' => 'Bintang hotel (dari master hotel)',
        'departure_city' => 'Kota berangkat',
        'arrival_city' => 'Tujuan penerbangan',
        'departure_date' => 'Tanggal berangkat',
    ];

    public const DEFAULT_DESCRIPTION = 'Paket {type_short} {package_kind} {duration_days} Hari dengan hotel bintang {hotel_stars}, maskapai {airline}, dan pendampingan muthawwif berbahasa Indonesia.';

    public static function render(string $template, array $attributes, ?PackageKind $kind = null): string
    {
        $text = strtr($template, self::replacementMap($attributes, $kind));
        $text = preg_replace('/\{[a-z_]+\}/', '', $text) ?? $text;
        $text = preg_replace('/\s{2,}/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, string>
     */
    public static function replacementMap(array $attributes, ?PackageKind $kind = null): array
    {
        $type = (string) ($attributes['type'] ?? '');
        $typeLabel = Package::TYPES[$type] ?? $type;

        $hotelMakkah = (string) ($attributes['hotel_makkah'] ?? '');
        $hotelMadinah = (string) ($attributes['hotel_madinah'] ?? '');
        $stars = self::resolveHotelStars($attributes, $hotelMakkah, $hotelMadinah);

        $departureDate = $attributes['departure_date'] ?? null;
        $departureDateLabel = filled($departureDate)
            ? Carbon::parse((string) $departureDate)->translatedFormat('d M Y')
            : '';

        $departureCity = (string) ($attributes['departure_city'] ?? '');
        $arrivalCity = (string) ($attributes['arrival_city'] ?? '');

        $vars = [
            'package_kind' => $kind?->name ?? '',
            'type' => $typeLabel,
            'type_short' => self::typeShortLabel($type, $typeLabel),
            'title' => (string) ($attributes['title'] ?? ''),
            'duration_days' => (string) ($attributes['duration_days'] ?? ''),
            'hari' => (string) ($attributes['duration_days'] ?? ''),
            'airline' => (string) ($attributes['airline'] ?? ''),
            'maskapai' => (string) ($attributes['airline'] ?? ''),
            'hotel_makkah' => $hotelMakkah,
            'hotel_madinah' => $hotelMadinah,
            'hotel_stars' => $stars !== null ? (string) $stars : '',
            'departure_city' => City::label($departureCity) ?: $departureCity,
            'arrival_city' => Package::arrivalCityLabelFor($arrivalCity) ?: $arrivalCity,
            'departure_date' => $departureDateLabel,
        ];

        $replacements = [];
        foreach ($vars as $key => $value) {
            $replacements['{'.$key.'}'] = $value;
        }

        return $replacements;
    }

    private static function typeShortLabel(string $type, string $fallback): string
    {
        return match ($type) {
            'umroh' => 'Umroh',
            'umroh_plus' => 'Umroh Plus',
            'haji_plus' => 'Haji Plus',
            default => $fallback,
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function resolveHotelStars(array $attributes, string $hotelMakkah, string $hotelMadinah): ?int
    {
        if (isset($attributes['hotel_stars']) && (int) $attributes['hotel_stars'] > 0) {
            return (int) $attributes['hotel_stars'];
        }

        $stars = array_filter([
            $hotelMakkah !== '' ? Hotel::starsFor(Hotel::LOCATION_MAKKAH, $hotelMakkah) : null,
            $hotelMadinah !== '' ? Hotel::starsFor(Hotel::LOCATION_MADINAH, $hotelMadinah) : null,
        ], fn (?int $value) => $value !== null && $value > 0);

        return $stars !== [] ? max($stars) : null;
    }
}
