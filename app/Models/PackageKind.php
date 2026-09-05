<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'sort_order', 'is_active'])]
class PackageKind extends Model
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
}
