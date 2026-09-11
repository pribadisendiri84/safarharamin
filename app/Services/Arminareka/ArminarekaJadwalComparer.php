<?php

namespace App\Services\Arminareka;

use App\Models\Package;
use App\Models\PriceSyncChange;
use Illuminate\Support\Collection;

class ArminarekaJadwalComparer
{
    /** @var list<string> */
    private const COMPARE_FIELDS = [
        'departure_date',
        'type',
        'package_kind_id',
        'departure_city',
        'arrival_city',
        'duration_days',
        'airline',
        'price_quad',
        'price_triple',
        'price_double',
        'seats_total',
        'seats_left',
    ];

    /** @var array<string, string> */
    public const FIELD_LABELS = [
        'title' => 'Judul',
        'departure_date' => 'Tanggal berangkat',
        'type' => 'Jenis paket',
        'package_kind_id' => 'Tipe paket',
        'departure_city' => 'Kota embarkasi',
        'arrival_city' => 'Tujuan penerbangan',
        'duration_days' => 'Durasi',
        'airline' => 'Maskapai',
        'price_quad' => 'Harga quad',
        'price_triple' => 'Harga triple',
        'price_double' => 'Harga double',
        'seats_total' => 'Quota',
        'seats_left' => 'Sisa seat',
        'source_key' => 'ID Arminareka',
    ];

    /**
     * @param  list<array<string, mixed>>  $incomingRows
     * @return list<array{
     *     package_id: int|null,
     *     source_key: string|null,
     *     external_id: string|null,
     *     change_status: string,
     *     existing_snapshot: array<string, mixed>|null,
     *     incoming_snapshot: array<string, mixed>|null,
     *     diff_fields: list<string>
     * }>
     */
    public function compare(array $incomingRows): array
    {
        $packages = Package::query()->get();
        $bySourceKey = $packages->filter(fn (Package $package) => filled($package->source_key))->keyBy('source_key');
        $matchedPackageIds = [];
        $changes = [];

        foreach ($incomingRows as $incoming) {
            $incomingSnapshot = $this->snapshotFromIncoming($incoming);
            $package = $this->matchExact($incoming, $bySourceKey, $packages);

            if ($package !== null) {
                $matchedPackageIds[] = $package->id;
                $existingSnapshot = $this->snapshotFromPackage($package);
                $diffFields = $this->diffFields($existingSnapshot, $incomingSnapshot);
                $diffFields = $this->appendSourceKeyLinkDiff($package, $incoming, $diffFields);

                $changes[] = [
                    'package_id' => $package->id,
                    'source_key' => $incoming['source_key'] ?? $package->source_key,
                    'external_id' => $incoming['external_id'] ?? null,
                    'change_status' => $diffFields === [] ? PriceSyncChange::STATUS_UNCHANGED : PriceSyncChange::STATUS_CHANGED,
                    'existing_snapshot' => $existingSnapshot,
                    'incoming_snapshot' => $incomingSnapshot,
                    'diff_fields' => $diffFields,
                ];

                continue;
            }

            $sibling = $this->findSiblingByTitle($incoming, $packages);

            if ($sibling !== null) {
                $incomingSnapshot['clone_from_package_id'] = $sibling->id;
                $incomingSnapshot['clone_from_title'] = $sibling->title;

                $changes[] = [
                    'package_id' => null,
                    'source_key' => $incoming['source_key'] ?? null,
                    'external_id' => $incoming['external_id'] ?? null,
                    'change_status' => PriceSyncChange::STATUS_NEW_DEPARTURE,
                    'existing_snapshot' => $this->snapshotFromPackage($sibling),
                    'incoming_snapshot' => $incomingSnapshot,
                    'diff_fields' => ['departure_date', 'price_quad', 'price_triple', 'price_double', 'seats_total', 'seats_left'],
                ];

                continue;
            }

            $changes[] = [
                'package_id' => null,
                'source_key' => $incoming['source_key'] ?? null,
                'external_id' => $incoming['external_id'] ?? null,
                'change_status' => PriceSyncChange::STATUS_NEW,
                'existing_snapshot' => null,
                'incoming_snapshot' => $incomingSnapshot,
                'diff_fields' => [],
            ];
        }

        foreach ($packages as $package) {
            if (! filled($package->source_key) || ! str_starts_with((string) $package->source_key, 'arminareka:')) {
                continue;
            }

            if (in_array($package->id, $matchedPackageIds, true)) {
                continue;
            }

            $changes[] = [
                'package_id' => $package->id,
                'source_key' => $package->source_key,
                'external_id' => str_replace('arminareka:', '', (string) $package->source_key),
                'change_status' => PriceSyncChange::STATUS_REMOVED,
                'existing_snapshot' => $this->snapshotFromPackage($package),
                'incoming_snapshot' => null,
                'diff_fields' => [],
            ];
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  Collection<string, Package>  $bySourceKey
     * @param  Collection<int, Package>  $packages
     */
    private function matchExact(array $incoming, $bySourceKey, $packages): ?Package
    {
        $sourceKey = (string) ($incoming['source_key'] ?? '');
        if ($sourceKey !== '' && $bySourceKey->has($sourceKey)) {
            return $bySourceKey->get($sourceKey);
        }

        $title = (string) ($incoming['title'] ?? '');
        $departureDate = $incoming['departure_date'] ?? null;

        if ($title === '' || ! filled($departureDate)) {
            return null;
        }

        return $packages->first(function (Package $package) use ($title, $departureDate) {
            return $this->packageBaseTitle($package) === $title
                && $package->departure_date?->toDateString() === $departureDate;
        });
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  Collection<int, Package>  $packages
     */
    private function findSiblingByTitle(array $incoming, $packages): ?Package
    {
        $title = (string) ($incoming['title'] ?? '');
        $departureDate = $incoming['departure_date'] ?? null;

        if ($title === '' || ! filled($departureDate)) {
            return null;
        }

        return $packages->first(function (Package $package) use ($title, $departureDate) {
            return $this->packageBaseTitle($package) === $title
                && $package->departure_date?->toDateString() !== $departureDate;
        });
    }

    private function packageBaseTitle(Package $package): string
    {
        return ArminarekaPeriodeParser::baseTitle((string) $package->title);
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function snapshotFromIncoming(array $incoming): array
    {
        return [
            'periode_full' => $incoming['periode_full'] ?? $incoming['title'] ?? null,
            'title' => $incoming['title'] ?? null,
            'type' => $incoming['type'] ?? null,
            'package_kind_id' => $incoming['package_kind_id'] ?? null,
            'departure_city' => $incoming['departure_city'] ?? null,
            'arrival_city' => $incoming['arrival_city'] ?? null,
            'departure_date' => $incoming['departure_date'] ?? null,
            'duration_days' => $incoming['duration_days'] ?? null,
            'airline' => $incoming['airline'] ?? null,
            'price_quad' => $incoming['price_quad'] ?? null,
            'price_triple' => $incoming['price_triple'] ?? null,
            'price_double' => $incoming['price_double'] ?? null,
            'seats_total' => $incoming['seats_total'] ?? null,
            'seats_left' => $incoming['seats_left'] ?? null,
            'status' => $incoming['status'] ?? null,
            'source_key' => $incoming['source_key'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFromPackage(Package $package): array
    {
        return [
            'periode_full' => $package->title,
            'title' => $this->packageBaseTitle($package),
            'type' => $package->type,
            'package_kind_id' => $package->package_kind_id,
            'departure_city' => $package->departure_city,
            'arrival_city' => $package->arrival_city,
            'departure_date' => $package->departure_date?->toDateString(),
            'duration_days' => $package->duration_days,
            'airline' => $package->airline,
            'price_quad' => $package->price_quad,
            'price_triple' => $package->price_triple,
            'price_double' => $package->price_double,
            'seats_total' => $package->seats_total,
            'seats_left' => $package->seats_left,
            'status' => $package->status,
            'source_key' => $package->source_key,
        ];
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  list<string>  $diffFields
     * @return list<string>
     */
    private function appendSourceKeyLinkDiff(Package $package, array $incoming, array $diffFields): array
    {
        $incomingSourceKey = $incoming['source_key'] ?? null;
        if (! filled($incomingSourceKey)) {
            return $diffFields;
        }

        if ((string) $package->source_key === (string) $incomingSourceKey) {
            return $diffFields;
        }

        if (! in_array('source_key', $diffFields, true)) {
            $diffFields[] = 'source_key';
        }

        return $diffFields;
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return list<string>
     */
    private function diffFields(array $existing, array $incoming): array
    {
        $diff = [];

        foreach (self::COMPARE_FIELDS as $field) {
            $left = $existing[$field] ?? null;
            $right = $incoming[$field] ?? null;

            if ($this->valuesEqual($left, $right)) {
                continue;
            }

            $diff[] = $field;
        }

        return $diff;
    }

    private function valuesEqual(mixed $left, mixed $right): bool
    {
        if ($left === $right) {
            return true;
        }

        if (is_numeric($left) && is_numeric($right)) {
            return (int) $left === (int) $right;
        }

        return (string) $left === (string) $right;
    }
}
