<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TableSort
{
    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, string>  $columns
     */
    public static function apply(
        Builder $query,
        Request $request,
        array $columns,
        string $defaultColumn = 'updated_at',
        string $defaultDirection = 'desc',
    ): void {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('dir')->toString() === 'asc' ? 'asc' : 'desc';

        if ($sort === '' || ! array_key_exists($sort, $columns)) {
            $query->orderBy($defaultColumn, $defaultDirection === 'asc' ? 'asc' : 'desc');

            return;
        }

        $query->orderBy($columns[$sort], $direction);

        if ($sort !== $defaultColumn) {
            $query->orderBy($defaultColumn, $defaultDirection === 'asc' ? 'asc' : 'desc');
        }
    }
}
