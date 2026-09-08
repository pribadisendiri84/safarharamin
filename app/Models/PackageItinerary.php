<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageItinerary extends Model
{
    public const KIND_OFFICIAL = 'official';

    public const KIND_SAMPLE = 'sample';

    protected $fillable = [
        'package_id',
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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('departure_date')->orderBy('sort_order')->orderBy('id');
    }

    public function scopeOfficial(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_OFFICIAL);
    }

    public function scopeSample(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_SAMPLE)->orderBy('sort_order')->orderBy('id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function displayLabel(): string
    {
        if ($this->kind === self::KIND_SAMPLE) {
            return trim((string) ($this->label ?? '')) ?: 'Contoh itinerary';
        }

        return $this->departure_date?->translatedFormat('d F Y') ?? 'Itinerary';
    }

    public function downloadFilename(): string
    {
        if ($this->kind === self::KIND_SAMPLE) {
            $slug = \Illuminate\Support\Str::slug($this->displayLabel()) ?: 'contoh-itinerary';

            return $slug.'.pdf';
        }

        return 'itinerary-'.$this->departure_date->format('Y-m-d').'.pdf';
    }

    public function isSample(): bool
    {
        return $this->kind === self::KIND_SAMPLE;
    }

    public static function syncSortOrderForPackage(int $packageId): void
    {
        $items = static::query()
            ->where('package_id', $packageId)
            ->official()
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
