<?php

namespace App\Support;

use App\Models\Airline;
use App\Models\Package;
use Illuminate\Support\Collection;

class PriceListGrouper
{
    /**
     * @param  Collection<int, Package>  $packages
     * @return Collection<string, Collection<string, Collection<int, Package>>>
     */
    public static function group(Collection $packages, string $emptyAirlineLabel = 'Maskapai menyusul'): Collection
    {
        /** @var array<string, int> $airlineSort */
        $airlineSort = Airline::query()->pluck('sort_order', 'name')->all();

        return $packages
            ->groupBy(fn (Package $package) => $package->typeLabel())
            ->map(function (Collection $typeGroup) use ($airlineSort, $emptyAirlineLabel) {
                return $typeGroup
                    ->groupBy(fn (Package $package) => filled($package->airline) ? $package->airline : $emptyAirlineLabel)
                    ->map(fn (Collection $airlineGroup) => self::sortPackages($airlineGroup))
                    ->sortBy(fn (Collection $group, string $airline) => sprintf(
                        '%04d-%s',
                        $airlineSort[$airline] ?? 9999,
                        $airline,
                    ));
            });
    }

    /**
     * Urutan baris: tipe paket (Arafah → Mina → Muzdalifah), durasi (9 → 12), tanggal, judul.
     *
     * @param  Collection<int, Package>  $packages
     * @return Collection<int, Package>
     */
    public static function sortPackages(Collection $packages): Collection
    {
        return $packages->sortBy(function (Package $package): string {
            return sprintf(
                '%04d-%04d-%s-%s',
                $package->packageKind?->sort_order ?? 9999,
                (int) ($package->duration_days ?? 9999),
                $package->departure_date?->format('Y-m-d') ?? '9999-99-99',
                $package->title,
            );
        })->values();
    }
}
