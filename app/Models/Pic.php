<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'phone', 'sort_order', 'is_active'])]
class Pic extends Model
{
    use RecordsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function pilgrims(): HasMany
    {
        return $this->hasMany(Pilgrim::class);
    }

    /**
     * @return array<int, string>
     */
    public static function options(?int $keepId = null): array
    {
        if (! Schema::hasTable('pics')) {
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
            ->get()
            ->mapWithKeys(fn (self $pic) => [
                $pic->id => $pic->is_active ? $pic->name : $pic->name.' (nonaktif)',
            ])
            ->all();
    }

    public static function firstOrCreateFromName(?string $name, ?string $phone = null): ?self
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $existing = static::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'name' => $name,
            'phone' => $phone ? trim($phone) : null,
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }
}
