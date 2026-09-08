<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HajiPageItinerary extends Model
{
    protected $fillable = [
        'kind',
        'label',
        'departure_date',
        'hijri_label',
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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('departure_date')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function departureLabel(): string
    {
        return $this->departure_date?->translatedFormat('d F Y') ?? '';
    }

    public function hijriLabel(): string
    {
        return trim((string) $this->hijri_label);
    }

    public function displayLabel(): string
    {
        $label = 'Keberangkatan '.$this->departureLabel();
        $hijri = $this->hijriLabel();

        return $hijri !== '' ? "{$label} · {$hijri}" : $label;
    }

    public function downloadFilename(): string
    {
        if ($this->departure_date !== null) {
            return 'itinerary-'.$this->departure_date->format('Y-m-d').'.pdf';
        }

        return 'itinerary.pdf';
    }

    public static function syncSortOrder(): void
    {
        $items = static::query()->ordered()->get();

        foreach ($items as $index => $item) {
            $sortOrder = $index + 1;
            if ((int) $item->sort_order !== $sortOrder) {
                $item->update(['sort_order' => $sortOrder]);
            }
        }
    }
}
