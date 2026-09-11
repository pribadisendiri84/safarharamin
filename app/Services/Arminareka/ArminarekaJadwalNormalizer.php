<?php

namespace App\Services\Arminareka;

use App\Models\Package;
use App\Models\PackageKind;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ArminarekaJadwalNormalizer
{
    /**
     * @param  list<array{
     *     external_id: string,
     *     row_number: int,
     *     periode: string,
     *     departure_date_label: string,
     *     price_quad: int|null,
     *     price_triple: int|null,
     *     price_double: int|null,
     *     quota: int,
     *     seats_left: int
     * }>  $rows
     * @return list<array{
     *     external_id: string,
     *     source_key: string,
     *     title: string,
     *     type: string,
     *     package_kind_label: string|null,
     *     package_kind_id: int|null,
     *     departure_city: string|null,
     *     departure_date: string|null,
     *     duration_days: int|null,
     *     airline: string|null,
     *     price_quad: int|null,
     *     price_triple: int|null,
     *     price_double: int|null,
     *     seats_total: int,
     *     seats_left: int,
     *     status: string,
     *     warnings: list<string>,
     *     raw: array<string, mixed>
     * }>
     */
    public function normalizeMany(array $rows): array
    {
        return array_values(array_map(fn (array $row) => $this->normalize($row), $rows));
    }

    /**
     * @param  array{
     *     external_id: string,
     *     row_number: int,
     *     periode: string,
     *     departure_date_label: string,
     *     price_quad: int|null,
     *     price_triple: int|null,
     *     price_double: int|null,
     *     quota: int,
     *     seats_left: int
     * }  $row
     * @return array{
     *     external_id: string,
     *     source_key: string,
     *     title: string,
     *     type: string,
     *     package_kind_label: string|null,
     *     package_kind_id: int|null,
     *     departure_city: string|null,
     *     departure_date: string|null,
     *     duration_days: int|null,
     *     airline: string|null,
     *     price_quad: int|null,
     *     price_triple: int|null,
     *     price_double: int|null,
     *     seats_total: int,
     *     seats_left: int,
     *     status: string,
     *     warnings: list<string>,
     *     raw: array<string, mixed>
     * }
     */
    public function normalize(array $row): array
    {
        $warnings = [];
        $periode = trim($row['periode']);
        $parsed = ArminarekaPeriodeParser::parse($periode);
        $kindLabel = $this->resolvePackageKindLabel($periode);
        $kind = $kindLabel !== null ? PackageKind::findActiveByLabel($kindLabel) : null;

        if ($kindLabel !== null && $kind === null) {
            $warnings[] = 'Tipe paket "'.$kindLabel.'" belum ada di master.';
        }

        $airline = $this->resolveAirline($periode);
        if ($airline === null) {
            $warnings[] = 'Maskapai tidak dikenali dari periode.';
        }

        $route = ArminarekaRouteParser::parse($periode);
        $departureCity = $route['departure_city'];
        $arrivalCity = $route['arrival_city'];

        if ($departureCity === null) {
            $warnings[] = 'Kota embarkasi tidak dikenali dari periode.';
        }

        if ($arrivalCity === null) {
            $warnings[] = 'Tujuan penerbangan tidak dikenali dari periode.';
        }

        $durationDays = $route['duration_days'] ?? $this->resolveDurationDays($periode);
        if ($durationDays === null) {
            $warnings[] = 'Durasi tidak dikenali dari periode.';
        }

        $departureDate = $this->resolveDepartureDate($row['departure_date_label'], $periode);
        if ($departureDate === null) {
            $warnings[] = 'Tanggal keberangkatan tidak valid.';
        }

        $seatsLeft = max(0, (int) $row['seats_left']);
        if ($row['seats_left'] < 0) {
            $warnings[] = 'Sisa seat negatif ('.$row['seats_left'].'), diset ke 0.';
        }

        $hasPrice = $row['price_quad'] !== null || $row['price_triple'] !== null || $row['price_double'] !== null;
        if (! $hasPrice) {
            $warnings[] = 'Semua harga kamar kosong.';
        }

        return [
            'external_id' => $row['external_id'],
            'source_key' => 'arminareka:'.$row['external_id'],
            'periode_full' => $parsed['periode_full'],
            'title' => $parsed['base_title'],
            'type' => $this->resolveType($periode),
            'package_kind_label' => $kindLabel,
            'package_kind_id' => $kind?->id,
            'departure_city' => $departureCity,
            'arrival_city' => $arrivalCity,
            'departure_date' => $departureDate,
            'duration_days' => $durationDays,
            'origin_airport' => $route['origin_airport'],
            'destination_airport' => $route['destination_airport'],
            'airline' => $airline,
            'price_quad' => $row['price_quad'],
            'price_triple' => $row['price_triple'],
            'price_double' => $row['price_double'],
            'seats_total' => max(0, (int) $row['quota']),
            'seats_left' => $seatsLeft,
            'status' => $seatsLeft === 0 ? 'fullbook' : 'draft',
            'warnings' => $warnings,
            'raw' => $row,
        ];
    }

    private function resolveType(string $periode): string
    {
        if (preg_match('/\bhaji\b/i', $periode) === 1) {
            return 'haji_plus';
        }

        if (preg_match('/\bplus\b/i', $periode) === 1) {
            return 'umroh_plus';
        }

        return 'umroh';
    }

    private function resolvePackageKindLabel(string $periode): ?string
    {
        if (preg_match('/\b(arofah|arofa)\b/i', $periode) === 1) {
            return 'Arafah';
        }

        if (preg_match('/\b(mina)\b/i', $periode) === 1) {
            return 'Mina';
        }

        if (preg_match('/\b(muzdalifah|mudzalifah)\b/i', $periode) === 1) {
            return 'Muzdalifah';
        }

        return null;
    }

    private function resolveAirline(string $periode): ?string
    {
        $codes = array_keys(config('arminareka.airline_codes', []));

        foreach ($codes as $code) {
            $pattern = '/\b'.preg_quote($code, '/').'\b/i';
            if (preg_match($pattern, $periode) === 1) {
                return config('arminareka.airline_codes.'.$code);
            }
        }

        return null;
    }

    private function resolveDurationDays(string $periode): ?int
    {
        if (preg_match('/\b(\d{1,2})\s*(?:D|HR|Hr|Hari)\b/i', $periode, $matches) === 1) {
            $days = (int) $matches[1];

            return $days >= 7 && $days <= 45 ? $days : null;
        }

        return null;
    }

    private function resolveDepartureDate(string $label, string $periode): ?string
    {
        foreach ([$label, $this->extractEmbeddedDate($periode)] as $candidate) {
            if (! filled($candidate)) {
                continue;
            }

            try {
                return Carbon::parse($candidate)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function extractEmbeddedDate(string $periode): ?string
    {
        if (preg_match('/\((\d{2}\/\d{2}\/\d{4})\)/', $periode, $matches) !== 1) {
            return null;
        }

        [$day, $month, $year] = array_map('intval', explode('/', $matches[1]));

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * @param  array{
     *     external_id: string,
     *     source_key: string,
     *     title: string,
     *     type: string,
     *     package_kind_label: string|null,
     *     package_kind_id: int|null,
     *     departure_city: string|null,
     *     departure_date: string|null,
     *     duration_days: int|null,
     *     airline: string|null,
     *     price_quad: int|null,
     *     price_triple: int|null,
     *     price_double: int|null,
     *     seats_total: int,
     *     seats_left: int,
     *     status: string,
     *     warnings: list<string>,
     *     raw: array<string, mixed>
     * }  $normalized
     * @return array<string, mixed>
     */
    public function toPackageAttributes(array $normalized): array
    {
        if (! array_key_exists($normalized['type'], Package::TYPES)) {
            throw new \InvalidArgumentException('Jenis paket tidak valid.');
        }

        if ($normalized['departure_city'] === null || $normalized['departure_date'] === null || $normalized['duration_days'] === null) {
            throw new \InvalidArgumentException('Data paket belum lengkap untuk disimpan.');
        }

        $roomType = $normalized['price_quad'] !== null
            ? 'quad'
            : ($normalized['price_triple'] !== null ? 'triple' : 'double');

        return [
            'title' => Str::limit($normalized['title'], 180, ''),
            'type' => $normalized['type'],
            'package_kind_id' => $normalized['package_kind_id'],
            'departure_city' => $normalized['departure_city'],
            'arrival_city' => $normalized['arrival_city'] ?? null,
            'departure_date' => $normalized['departure_date'],
            'departure_date_display' => 'single',
            'duration_days' => $normalized['duration_days'],
            'price_quad' => $normalized['price_quad'],
            'price_triple' => $normalized['price_triple'],
            'price_double' => $normalized['price_double'],
            'airline' => $normalized['airline'],
            'room_type' => $roomType,
            'seats_total' => $normalized['seats_total'],
            'seats_left' => $normalized['seats_left'],
            'show_seats' => true,
            'status' => $normalized['status'],
        ];
    }
}
