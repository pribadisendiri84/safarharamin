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

    public function dbPeriodeLabel(): string
    {
        $existing = $this->existing_snapshot ?? [];
        $title = $existing['periode_full'] ?? $existing['title'] ?? null;

        return filled($title) ? (string) $title : '—';
    }

    public function dbDepartureDateLabel(): string
    {
        $date = $this->existing_snapshot['departure_date'] ?? null;

        if (! filled($date)) {
            return '—';
        }

        return Carbon::parse($date)->translatedFormat('d M Y');
    }

    public function showsWebPeriodeHint(): bool
    {
        if (! in_array($this->change_status, [self::STATUS_CHANGED, self::STATUS_UNCHANGED], true)) {
            return false;
        }

        $existing = $this->existing_snapshot ?? [];
        $incoming = $this->incoming_snapshot ?? [];
        $dbTitle = (string) ($existing['title'] ?? $existing['periode_full'] ?? '');
        $webTitle = (string) ($incoming['title'] ?? $incoming['periode_full'] ?? '');

        return $dbTitle !== '' && $webTitle !== '' && $dbTitle !== $webTitle;
    }

    public function highlightsDiff(): bool
    {
        return in_array($this->change_status, [
            self::STATUS_NEW,
            self::STATUS_NEW_DEPARTURE,
            self::STATUS_CHANGED,
            self::STATUS_REMOVED,
        ], true);
    }

    /**
     * @return list<array{label: string, from: string, to: string}>
     */
    public function diffDetailRows(): array
    {
        $existing = $this->existing_snapshot ?? [];
        $incoming = $this->incoming_snapshot ?? [];
        $rows = [];

        foreach ($this->diff_fields ?? [] as $field) {
            $label = \App\Services\Arminareka\ArminarekaJadwalComparer::FIELD_LABELS[$field] ?? $field;
            $rows[] = [
                'label' => $label,
                'from' => Package::formatSyncSnapshotValue($field, $existing[$field] ?? null),
                'to' => Package::formatSyncSnapshotValue($field, $incoming[$field] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, detail: string}>
     */
    public function diffDetails(): array
    {
        return array_map(
            fn (array $row) => [
                'label' => $row['label'],
                'detail' => $row['label'].': '.$row['from'].' → '.$row['to'],
            ],
            $this->diffDetailRows(),
        );
    }

    /**
     * @return list<array{type: string, label?: string, from?: string, to?: string, text?: string}>
     */
    public function bedanyaRows(): array
    {
        return match ($this->change_status) {
            self::STATUS_CHANGED => array_map(
                fn (array $row) => ['type' => 'diff'] + $row,
                $this->diffDetailRows(),
            ),
            self::STATUS_NEW => $this->incomingSummaryRows(),
            self::STATUS_NEW_DEPARTURE => array_merge(
                [['type' => 'note', 'text' => 'Keberangkatan baru dari '.$this->cloneSourceLabel().'.']],
                array_map(
                    fn (array $row) => ['type' => 'diff'] + $row,
                    $this->diffDetailRows(),
                ),
                $this->incomingSummaryRows($this->diff_fields ?? []),
            ),
            self::STATUS_REMOVED => [['type' => 'note', 'text' => 'Tidak ditemukan lagi di Arminareka.']],
            default => [],
        };
    }

    private function cloneSourceLabel(): string
    {
        $incoming = $this->incoming_snapshot ?? [];

        return (string) (
            $incoming['clone_from_title']
            ?? $this->existing_snapshot['title']
            ?? 'paket induk'
        );
    }

    /**
     * @param  list<string>  $excludeDiffFields
     * @return list<array{type: string, text: string}>
     */
    private function incomingSummaryRows(array $excludeDiffFields = []): array
    {
        $incoming = $this->incoming_snapshot ?? [];
        $rows = [];

        if ($this->change_status === self::STATUS_NEW) {
            $rows[] = ['type' => 'note', 'text' => $this->periodeLabel()];
        }

        if (filled($incoming['departure_date'] ?? null)
            && ! in_array('departure_date', $excludeDiffFields, true)) {
            $rows[] = [
                'type' => 'note',
                'text' => 'Tanggal '.Carbon::parse($incoming['departure_date'])->translatedFormat('d M Y'),
            ];
        }

        $priceLine = $this->formatIncomingPriceLine($incoming, $excludeDiffFields);
        if ($priceLine !== '') {
            $rows[] = ['type' => 'note', 'text' => $priceLine];
        }

        $seatsLeft = $incoming['seats_left'] ?? null;
        $seatsTotal = $incoming['seats_total'] ?? null;
        if (($seatsLeft !== null || $seatsTotal !== null)
            && ! in_array('seats_left', $excludeDiffFields, true)
            && ! in_array('seats_total', $excludeDiffFields, true)) {
            $rows[] = [
                'type' => 'note',
                'text' => 'Seat '.(int) ($seatsLeft ?? 0).'/'.(int) ($seatsTotal ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @param  list<string>  $excludeDiffFields
     */
    private function formatIncomingPriceLine(array $incoming, array $excludeDiffFields = []): string
    {
        $parts = [];

        foreach (['price_quad' => 'Q', 'price_triple' => 'T', 'price_double' => 'D'] as $field => $prefix) {
            if (in_array($field, $excludeDiffFields, true)) {
                continue;
            }

            $amount = $incoming[$field] ?? null;
            if (is_numeric($amount) && (int) $amount > 0) {
                $parts[] = $prefix.' Rp'.number_format((int) $amount, 0, ',', '.');
            }
        }

        return implode(' · ', $parts);
    }

    public function departureDateLabel(): ?string
    {
        $date = $this->incoming_snapshot['departure_date'] ?? $this->existing_snapshot['departure_date'] ?? null;

        if (! filled($date)) {
            return null;
        }

        return Carbon::parse($date)->translatedFormat('d M Y');
    }

    public function reportPackageTitle(): string
    {
        if ($this->package !== null) {
            return (string) $this->package->title;
        }

        $existing = $this->existing_snapshot ?? [];
        $incoming = $this->incoming_snapshot ?? [];

        return (string) (
            $existing['periode_full']
            ?? $existing['title']
            ?? $incoming['periode_full']
            ?? $incoming['title']
            ?? $this->source_key
            ?? '—'
        );
    }

    public function appliedSummaryLabel(): string
    {
        return match ($this->change_status) {
            self::STATUS_NEW => 'Produk baru diterapkan',
            self::STATUS_NEW_DEPARTURE => 'Keberangkatan baru diterapkan',
            self::STATUS_CHANGED => 'Paket diperbarui',
            self::STATUS_REMOVED => 'Paket disembunyikan',
            default => $this->statusLabel(),
        };
    }
}
