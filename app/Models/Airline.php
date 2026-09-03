<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'sort_order', 'is_active'])]
class Airline extends Model
{
    use RecordsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(?string $keep = null): array
    {
        if (! Schema::hasTable('airlines')) {
            return $keep ? [$keep => $keep] : [];
        }

        $options = static::query()
            ->when(
                $keep,
                fn ($query) => $query->where(fn ($inner) => $inner->where('is_active', true)->orWhere('name', $keep)),
                fn ($query) => $query->where('is_active', true),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();

        if ($keep && ! array_key_exists($keep, $options)) {
            $options = [$keep => $keep.' (legacy)'] + $options;
        }

        return $options;
    }
}
