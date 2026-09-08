<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HajiPageItinerary extends Model
{
    public const KIND_OFFICIAL = 'official';

    public const KIND_SAMPLE = 'sample';

    protected $fillable = [
        'kind',
        'label',
        'departure_date',
        'file_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function scopeOfficial(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_OFFICIAL);
    }

    public function scopeSample(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_SAMPLE);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw('departure_date is null')
            ->orderBy('departure_date')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function displayLabel(): string
    {
        if ($this->kind === self::KIND_OFFICIAL && $this->departure_date !== null) {
            return $this->departure_date->translatedFormat('d F Y');
        }

        return trim((string) $this->label) ?: 'Itinerary';
    }

    public function downloadFilename(): string
    {
        if ($this->kind === self::KIND_OFFICIAL && $this->departure_date !== null) {
            return 'itinerary-'.$this->departure_date->format('Y-m-d').'.pdf';
        }

        $slug = str($this->label ?: 'sample-itinerary')->slug('-');

        return $slug.'.pdf';
    }

    public static function syncSortOrder(string $kind): void
    {
        $items = static::query()
            ->where('kind', $kind)
            ->ordered()
            ->get();

        foreach ($items as $index => $item) {
            $sortOrder = $index + 1;
            if ((int) $item->sort_order !== $sortOrder) {
                $item->update(['sort_order' => $sortOrder]);
            }
        }
    }
}
