<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'location', 'sort_order', 'is_active'])]
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
        ];
    }

    public function locationLabel(): string
    {
        return self::LOCATIONS[$this->location] ?? $this->location;
    }

    /**
     * @return array<string, string>
     */
    public static function options(string $location, ?string $keep = null): array
    {
        if (! Schema::hasTable('hotels')) {
            return self::legacyOption($keep);
        }

        $options = static::query()
            ->where('location', $location)
            ->when(
                $keep,
                fn ($query) => $query->where(fn ($inner) => $inner->where('is_active', true)->orWhere('name', $keep)),
                fn ($query) => $query->where('is_active', true),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();

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
