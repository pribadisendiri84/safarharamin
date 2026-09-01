<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'sort_order', 'is_active'])]
class City extends Model
{
    use RecordsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (City $city) {
            if ($city->slug === null || $city->slug === '') {
                $city->slug = static::uniqueSlug($city->name, $city->id);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public static function options(?string $keep = null): array
    {
        if (! Schema::hasTable('cities')) {
            return [];
        }

        return static::query()
            ->when(
                $keep,
                fn ($query) => $query->where(fn ($inner) => $inner->where('is_active', true)->orWhere('slug', $keep)),
                fn ($query) => $query->where('is_active', true),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();
    }

    public static function label(?string $slug): string
    {
        if (! filled($slug)) {
            return '';
        }

        if (Schema::hasTable('cities')) {
            $name = static::withTrashed()->where('slug', $slug)->value('name');
            if (filled($name)) {
                return $name;
            }
        }

        return Str::headline($slug);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kota';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
