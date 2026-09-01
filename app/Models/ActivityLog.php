<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id',
    'actor_name',
    'action',
    'subject_type',
    'subject_id',
    'subject_label',
    'properties',
    'created_at',
])]
class ActivityLog extends Model
{
    public $timestamps = false;

    public const ACTIONS = [
        'created' => 'Dibuat',
        'updated' => 'Diperbarui',
        'deleted' => 'Dihapus',
        'restored' => 'Dipulihkan',
    ];

    public const SUBJECTS = [
        User::class => 'Pengguna',
        Package::class => 'Paket',
        GalleryItem::class => 'Galeri',
        Testimonial::class => 'Testimoni',
        Inquiry::class => 'Pengajuan',
        City::class => 'Kota',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function subjectTypeLabel(): string
    {
        return self::SUBJECTS[$this->subject_type] ?? class_basename($this->subject_type);
    }

    public function actorLabel(): string
    {
        return $this->actor_name ?: $this->actor?->name ?: 'Sistem';
    }

    /**
     * @return array<string, mixed>
     */
    public function changedFields(): array
    {
        $properties = $this->properties ?? [];

        return $properties['changes'] ?? $properties;
    }
}
