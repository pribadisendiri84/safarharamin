<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'trigger',
    'user_id',
    'status',
    'total_found',
    'total_new',
    'total_changed',
    'total_unchanged',
    'total_removed',
    'total_applied',
    'error_message',
    'started_at',
    'ended_at',
])]
class PriceSyncRun extends Model
{
    public const TRIGGER_MANUAL = 'manual';

    public const TRIGGER_SCHEDULED = 'scheduled';

    public const STATUS_WAITING = 'waiting_approval';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PARTIAL = 'partial';

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(PriceSyncChange::class);
    }

    public function pendingChanges(): HasMany
    {
        return $this->changes()->whereNull('applied_at');
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function reference(): string
    {
        return '#'.$this->created_at?->format('Ymd').'-'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }
}
