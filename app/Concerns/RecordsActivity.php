<?php

namespace App\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait RecordsActivity
{
    /**
     * @var list<string>
     */
    protected array $activityHidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var list<string>
     */
    protected array $activityIgnore = [
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'remember_token',
    ];

    public static function bootRecordsActivity(): void
    {
        static::creating(function (self $model): void {
            if ($id = Auth::id()) {
                $model->created_by ??= $id;
                $model->updated_by = $id;
            }
        });

        static::updating(function (self $model): void {
            if ($id = Auth::id()) {
                $model->updated_by = $id;
            }
        });

        static::created(function (self $model): void {
            $model->recordActivity('created');
        });

        static::updated(function (self $model): void {
            if ($model->activityChangedKeys() === []) {
                return;
            }

            $model->recordActivity('updated');
        });

        static::deleted(function (self $model): void {
            $model->recordActivity('deleted');
        });

        static::restored(function (self $model): void {
            $model->recordActivity('restored');
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by')->withTrashed();
    }

    /**
     * @return list<string>
     */
    protected function activityIgnoreKeys(): array
    {
        return $this->activityIgnore;
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by')->withTrashed();
    }

    public function activityLabel(): string
    {
        foreach (['title', 'name', 'email'] as $field) {
            $value = trim((string) ($this->getAttribute($field) ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return class_basename($this).' #'.$this->getKey();
    }

    protected function recordActivity(string $action): void
    {
        if (! Schema::hasTable('activity_logs') || ! $this->getKey()) {
            return;
        }

        $user = Auth::user();

        ActivityLog::query()->create([
            'user_id' => $user?->id,
            'actor_name' => $user?->name,
            'action' => $action,
            'subject_type' => $this->getMorphClass(),
            'subject_id' => $this->getKey(),
            'subject_label' => $this->activityLabel(),
            'properties' => $this->activityProperties($action),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function activityProperties(string $action): ?array
    {
        if ($action === 'updated') {
            $keys = $this->activityChangedKeys();
            $old = [];
            $new = [];

            foreach ($keys as $key) {
                $old[$key] = $this->sanitizeActivityValue($key, $this->getOriginal($key));
                $new[$key] = $this->sanitizeActivityValue($key, $this->getAttribute($key));
            }

            return ['old' => $old, 'new' => $new];
        }

        if ($action === 'created') {
            return ['new' => $this->activitySnapshot()];
        }

        return ['label' => $this->activityLabel()];
    }

    /**
     * @return list<string>
     */
    protected function activityChangedKeys(): array
    {
        return collect($this->getChanges())
            ->keys()
            ->reject(fn (string $key) => in_array($key, $this->activityIgnoreKeys(), true))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function activitySnapshot(): array
    {
        return collect($this->attributesToArray())
            ->reject(fn (mixed $value, string $key) => in_array($key, $this->activityIgnoreKeys(), true)
                || in_array($key, $this->activityHidden, true))
            ->all();
    }

    protected function sanitizeActivityValue(string $key, mixed $value): mixed
    {
        if (in_array($key, $this->activityHidden, true) && filled($value)) {
            return '(diubah)';
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }
}
