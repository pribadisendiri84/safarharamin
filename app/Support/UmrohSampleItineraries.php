<?php

namespace App\Support;

use App\Models\Setting;

class UmrohSampleItineraries
{
    public const KEY = 'umroh_sample_itineraries';

    /**
     * @return list<array{label: string, file_path: string}>
     */
    public static function all(): array
    {
        $raw = Setting::getValue(self::KEY);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return self::normalize(is_array($decoded) ? $decoded : []);
    }

    /**
     * @param  list<array{label: string, file_path: string}>  $items
     */
    public static function save(array $items): void
    {
        Setting::setValue(
            self::KEY,
            json_encode(self::normalize($items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]',
        );
    }

    /**
     * @param  list<mixed>  $items
     * @return list<array{label: string, file_path: string}>
     */
    public static function normalize(array $items): array
    {
        $rows = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $path = trim((string) ($item['file_path'] ?? ''));

            if ($label === '' || $path === '') {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'file_path' => $path,
            ];
        }

        return array_slice($rows, 0, 2);
    }
}
