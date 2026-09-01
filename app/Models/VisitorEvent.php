<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable([
    'type',
    'session_id',
    'path',
    'landing_path',
    'referrer_host',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'wa_placement',
    'created_at',
])]
class VisitorEvent extends Model
{
    public $timestamps = false;

    public const TYPE_PAGE_VIEW = 'page_view';

    public const TYPE_WA_CLICK = 'wa_click';

    public const PLACEMENTS = [
        'header' => 'Header',
        'float' => 'Tombol mengambang',
        'form' => 'Setelah form',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<VisitorEvent>  $query
     * @return Builder<VisitorEvent>
     */
    public function scopePageViews(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PAGE_VIEW);
    }

    /**
     * @param  Builder<VisitorEvent>  $query
     * @return Builder<VisitorEvent>
     */
    public function scopeWaClicks(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_WA_CLICK);
    }

    /**
     * @param  Builder<VisitorEvent>  $query
     * @return Builder<VisitorEvent>
     */
    public function scopeSince(Builder $query, Carbon $from): Builder
    {
        return $query->where('created_at', '>=', $from);
    }

    public function placementLabel(): string
    {
        return self::PLACEMENTS[$this->wa_placement] ?? ($this->wa_placement ?: '-');
    }

    public function sourceLabel(): string
    {
        if (filled($this->utm_source)) {
            $label = $this->utm_source;
            if (filled($this->utm_medium)) {
                $label .= ' / '.$this->utm_medium;
            }
            if (filled($this->utm_campaign)) {
                $label .= ' · '.$this->utm_campaign;
            }

            return $label;
        }

        return $this->referrer_host ?: 'Langsung';
    }

    public static function uniqueVisitors(?Carbon $from = null): int
    {
        $query = static::query()->pageViews();
        if ($from) {
            $query->since($from);
        }

        return (int) $query->selectRaw('count(distinct session_id) as aggregate')->value('aggregate');
    }

    public static function uniqueWaClickers(?Carbon $from = null): int
    {
        $query = static::query()->waClicks();
        if ($from) {
            $query->since($from);
        }

        return (int) $query->selectRaw('count(distinct session_id) as aggregate')->value('aggregate');
    }
}
