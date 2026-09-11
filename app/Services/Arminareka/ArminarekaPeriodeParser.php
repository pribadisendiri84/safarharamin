<?php

namespace App\Services\Arminareka;

class ArminarekaPeriodeParser
{
    /**
     * @return array{periode_full: string, base_title: string, embedded_date: string|null}
     */
    public static function parse(string $periode): array
    {
        $periode = trim($periode);

        if (preg_match('/^(.+?)\s*\((\d{2}\/\d{2}\/\d{4})\)\s*$/u', $periode, $matches) === 1) {
            return [
                'periode_full' => $periode,
                'base_title' => trim($matches[1]),
                'embedded_date' => self::dmYToIso($matches[2]),
            ];
        }

        return [
            'periode_full' => $periode,
            'base_title' => $periode,
            'embedded_date' => null,
        ];
    }

    public static function baseTitle(string $value): string
    {
        return self::parse($value)['base_title'];
    }

    private static function dmYToIso(string $value): ?string
    {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $matches) !== 1) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
    }
}
