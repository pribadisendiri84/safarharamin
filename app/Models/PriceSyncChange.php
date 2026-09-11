<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'price_sync_run_id',
    'package_id',
    'source_key',
    'external_id',
    'change_status',
    'existing_snapshot',
    'incoming_snapshot',
    'diff_fields',
    'applied_at',
])]
class PriceSyncChange extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_NEW_DEPARTURE = 'new_departure';

    public const STATUS_CHANGED = 'changed';

    public const STATUS_UNCHANGED = 'unchanged';

    public const STATUS_REMOVED = 'removed';

    public const STATUSES = [
        self::STATUS_NEW => 'Produk baru',
        self::STATUS_NEW_DEPARTURE => 'Keberangkatan baru',
        self::STATUS_CHANGED => 'Update harga',
        self::STATUS_UNCHANGED => 'Tidak berubah',
        self::STATUS_REMOVED => 'Tidak ditemukan',
    ];

    protected function casts(): array
    {
        return [
            'existing_snapshot' => 'array',
            'incoming_snapshot' => 'array',
            'diff_fields' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PriceSyncRun::class, 'price_sync_run_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function isApplicable(): bool
    {
        return in_array($this->change_status, [
            self::STATUS_NEW,
            self::STATUS_NEW_DEPARTURE,
            self::STATUS_CHANGED,
            self::STATUS_REMOVED,
        ], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->change_status] ?? $this->change_status;
    }

    public function periodeLabel(): string
    {
        return (string) (
            $this->incoming_snapshot['periode_full']
            ?? $this->incoming_snapshot['title']
            ?? $this->existing_snapshot['periode_full']
            ?? $this->existing_snapshot['title']
            ?? '-'
        );
    }

    /**
     * @return list<string>
     */
    public function diffFieldLabels(): array
    {
        return array_map(
            fn (array $detail) => $detail['label'],
            $this->diffDetails(),
        );
    }

    /**
     * @return list<array{label: string, detail: string}>
     */
    public function diffDetails(): array
    {
        $existing = $this->existing_snapshot ?? [];
        $incoming = $this->incoming_snapshot ?? [];
        $details = [];

        foreach ($this->diff_fields ?? [] as $field) {
            $label = \App\Services\Arminareka\ArminarekaJadwalComparer::FIELD_LABELS[$field] ?? $field;
            $from = Package::formatSyncSnapshotValue($field, $existing[$field] ?? null);
            $to = Package::formatSyncSnapshotValue($field, $incoming[$field] ?? null);

            $details[] = [
                'label' => $label,
                'detail' => $label.': '.$from.' → '.$to,
            ];
        }

        return $details;
    }

    public function departureDateLabel(): ?string
    {
        $date = $this->incoming_snapshot['departure_date'] ?? $this->existing_snapshot['departure_date'] ?? null;

        if (! filled($date)) {
            return null;
        }

        return Carbon::parse($date)->translatedFormat('d M Y');
    }
}
