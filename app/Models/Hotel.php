<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'location', 'stars', 'sort_order', 'is_active'])]
class Hotel extends Model
{
    use RecordsActivity, SoftDeletes;

    public const LOCATION_MAKKAH = 'makkah';

    public const LOCATION_MADINAH = 'madinah';

    public const LOCATION_TRANSIT = 'transit';

    public const LOCATION_MAKTAB = 'maktab';

    public const LOCATIONS = [
        self::LOCATION_MAKKAH => 'Makkah',
        self::LOCATION_MADINAH => 'Madinah',
        self::LOCATION_TRANSIT => 'Transit',
        self::LOCATION_MAKTAB => 'Maktab',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'stars' => 'integer',
        ];
    }

    public function locationLabel(): string
    {
        return self::LOCATIONS[$this->location] ?? $this->location;
    }

    public function optionLabel(): string
    {
        $stars = (int) ($this->stars ?: 0);

        return $stars > 0 ? $this->name.' ('.$stars.'★)' : $this->name;
    }

    public static function starsFor(string $location, string $name): ?int
    {
        if (! Schema::hasTable('hotels') || ! Schema::hasColumn('hotels', 'stars')) {
            return null;
        }

        $stars = static::query()
            ->where('location', $location)
            ->where('name', $name)
            ->value('stars');

        return $stars !== null ? (int) $stars : null;
    }

    /**
     * @return array<string, string>
     */
    public static function options(string $location, ?string $keep = null): array
    {
        if (! Schema::hasTable('hotels')) {
            return self::legacyOption($keep);
        }

        $rows = static::query()
            ->where('location', $location)
            ->when(
                $keep,
                fn ($query) => $query->where(fn ($inner) => $inner->where('is_active', true)->orWhere('name', $keep)),
                fn ($query) => $query->where('is_active', true),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'stars']);

        $options = $rows->mapWithKeys(fn (self $hotel) => [$hotel->name => $hotel->optionLabel()])->all();

        return self::mergeLegacyOption($options, $keep);
    }

    /**
     * @return array<string, string>
     */
    private static function legacyOption(?string $keep): array
    {
        return $keep ? [$keep => $keep] : [];
    }

    /**
     * @param  array<string, string>  $options
     * @return array<string, string>
     */
    private static function mergeLegacyOption(array $options, ?string $keep): array
    {
        if ($keep && ! array_key_exists($keep, $options)) {
            $options = [$keep => $keep.' (legacy)'] + $options;
        }

        return $options;
    }
}
