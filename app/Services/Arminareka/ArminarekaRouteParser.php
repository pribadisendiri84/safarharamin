<?php

namespace App\Services\Arminareka;

class ArminarekaRouteParser
{
    /**
     * @return array{
     *     origin_airport: string|null,
     *     destination_airport: string|null,
     *     departure_city: string|null,
     *     arrival_city: string|null,
     *     duration_days: int|null
     * }
     */
    public static function parse(string $periode): array
    {
        $baseTitle = ArminarekaPeriodeParser::baseTitle($periode);
        $origins = config('arminareka.origin_airports', config('arminareka.airport_cities', []));
        $destinations = config('arminareka.destination_airports', []);

        $originAirport = null;
        $destinationAirport = null;
        $durationDays = null;

        if (preg_match('/\b([A-Z]{3})\s+(\d{1,2})\s*D\s+([A-Z]{3})\b/i', $baseTitle, $matches) === 1) {
            $originAirport = strtoupper($matches[1]);
            $durationDays = (int) $matches[2];
            $destinationAirport = strtoupper($matches[3]);
        }

        if ($durationDays === null && preg_match('/\b(\d{1,2})\s*D\b/i', $baseTitle, $durationMatch) === 1) {
            $durationDays = (int) $durationMatch[1];
            $durationDays = ($durationDays >= 7 && $durationDays <= 45) ? $durationDays : null;
        }

        if ($originAirport === null) {
            $originAirport = self::firstAirportCode($baseTitle, array_keys($origins));
        }

        if ($destinationAirport === null) {
            $destinationAirport = self::lastAirportCode($baseTitle, array_keys($destinations));
        }

        $departureCity = $originAirport !== null ? ($origins[$originAirport] ?? null) : null;
        $arrivalCity = $destinationAirport !== null ? ($destinations[$destinationAirport] ?? null) : null;

        if ($durationDays !== null && ($durationDays < 7 || $durationDays > 45)) {
            $durationDays = null;
        }

        return [
            'origin_airport' => $originAirport,
            'destination_airport' => $destinationAirport,
            'departure_city' => $departureCity,
            'arrival_city' => $arrivalCity,
            'duration_days' => $durationDays,
        ];
    }

    /**
     * @param  list<string>  $codes
     */
    private static function firstAirportCode(string $text, array $codes): ?string
    {
        if ($codes === []) {
            return null;
        }

        $pattern = '/\b(?:'.implode('|', array_map(fn (string $code) => preg_quote($code, '/'), $codes)).')\b/i';

        if (preg_match($pattern, $text, $matches) !== 1) {
            return null;
        }

        return strtoupper($matches[0]);
    }

    /**
     * @param  list<string>  $codes
     */
    private static function lastAirportCode(string $text, array $codes): ?string
    {
        if ($codes === []) {
            return null;
        }

        $pattern = '/\b(?:'.implode('|', array_map(fn (string $code) => preg_quote($code, '/'), $codes)).')\b/i';

        if (preg_match_all($pattern, $text, $matches) < 1) {
            return null;
        }

        return strtoupper((string) end($matches[0]));
    }
}
