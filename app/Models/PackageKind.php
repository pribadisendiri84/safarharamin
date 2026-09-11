<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Support\PackageKindContentTemplate;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'sort_order',
    'is_active',
    'description',
    'hotel_makkah',
    'hotel_makkah_setaraf',
    'hotel_madinah',
    'hotel_madinah_setaraf',
    'facilities',
    'exclusions',
])]
class PackageKind extends Model
{
    use RecordsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'hotel_makkah_setaraf' => 'boolean',
            'hotel_madinah_setaraf' => 'boolean',
            'facilities' => 'array',
            'exclusions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $kind): void {
            if (! filled($kind->slug)) {
                $kind->slug = Str::slug($kind->name) ?: 'tipe-paket';
            }
        });
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public static function findActiveByLabel(string $label): ?self
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        $slug = Str::slug($label);

        return static::query()
            ->where('is_active', true)
            ->where(function ($query) use ($slug, $label) {
                $query->where('slug', $slug)
                    ->orWhereRaw('lower(name) = ?', [mb_strtolower($label)]);
            })
            ->first();
    }

    /**
     * @return array<int, string>
     */
    public static function options(?int $keepId = null): array
    {
        if (! Schema::hasTable('package_kinds')) {
            return [];
        }

        return static::query()
            ->when(
                $keepId,
                fn ($query) => $query->where(fn ($inner) => $inner->where('is_active', true)->orWhere('id', $keepId)),
                fn ($query) => $query->where('is_active', true),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function hasContentTemplate(): bool
    {
        return filled($this->description)
            || filled($this->hotel_makkah)
            || filled($this->hotel_madinah)
            || $this->hasTemplateLines($this->facilities)
            || $this->hasTemplateLines($this->exclusions);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function mergeMissingTemplateInto(array $attributes): array
    {
        if (blank($attributes['hotel_makkah'] ?? null) && filled($this->hotel_makkah)) {
            $attributes['hotel_makkah'] = $this->hotel_makkah;
            $attributes['hotel_makkah_setaraf'] = $this->hotel_makkah_setaraf;
        }

        if (blank($attributes['hotel_madinah'] ?? null) && filled($this->hotel_madinah)) {
            $attributes['hotel_madinah'] = $this->hotel_madinah;
            $attributes['hotel_madinah_setaraf'] = $this->hotel_madinah_setaraf;
        }

        if (! $this->hasTemplateLines($attributes['facilities'] ?? null) && $this->hasTemplateLines($this->facilities)) {
            $attributes['facilities'] = $this->facilities;
        }

        if (! $this->hasTemplateLines($attributes['exclusions'] ?? null) && $this->hasTemplateLines($this->exclusions)) {
            $attributes['exclusions'] = $this->exclusions;
        }

        if (blank($attributes['description'] ?? null) && filled($this->description)) {
            $attributes['description'] = PackageKindContentTemplate::render(
                $this->description,
                $attributes,
                $this,
            );
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function mergeMissingTemplateForKind(?int $kindId, array $attributes): array
    {
        if ($kindId === null) {
            return $attributes;
        }

        $kind = static::query()->find($kindId);

        return $kind ? $kind->mergeMissingTemplateInto($attributes) : $attributes;
    }

    /**
     * @param  list<string>|null  $lines
     */
    private function hasTemplateLines(?array $lines): bool
    {
        if ($lines === null) {
            return false;
        }

        foreach ($lines as $line) {
            if (trim((string) $line) !== '') {
                return true;
            }
        }

        return false;
    }
}
