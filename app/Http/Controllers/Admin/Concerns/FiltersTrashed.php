<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait FiltersTrashed
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyTrashFilter(Builder $query, Request $request): Builder
    {
        return $request->boolean('trashed')
            ? $query->onlyTrashed()
            : $query;
    }

    /**
     * @param  class-string<Model>  $model
     * @return array{trashed: bool, trashedCount: int}
     */
    protected function trashViewData(string $model, Request $request): array
    {
        return [
            'trashed' => $request->boolean('trashed'),
            'trashedCount' => $model::onlyTrashed()->count(),
        ];
    }
}
