<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageItinerary extends Model
{
    protected $fillable = [
        'package_id',
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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('departure_date')->orderBy('sort_order')->orderBy('id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function displayLabel(): string
    {
        return $this->departure_date->translatedFormat('d F Y');
    }

    public function downloadFilename(): string
    {
        return 'itinerary-'.$this->departure_date->format('Y-m-d').'.pdf';
    }

    public static function syncSortOrderForPackage(int $packageId): void
    {
        $items = static::query()
            ->where('package_id', $packageId)
            ->orderBy('departure_date')
            ->orderBy('id')
            ->get();

        foreach ($items as $index => $item) {
            $sortOrder = $index + 1;
            if ((int) $item->sort_order !== $sortOrder) {
                $item->update(['sort_order' => $sortOrder]);
            }
        }
    }
}
