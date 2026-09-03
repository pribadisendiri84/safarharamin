<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pilgrim_id',
    'type',
    'amount',
    'paid_at',
    'notes',
    'proof_path',
    'invoice_number',
    'invoice_created_at',
    'created_by',
])]
class PilgrimTransaction extends Model
{
    public const TYPE_DP = 'dp';

    public const TYPE_SETTLEMENT = 'pelunasan';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_PORSI = 'porsi';

    public const TYPE_OTHER = 'lain-lain';

    /** @return array<string, string> */
    public static function baseTypes(): array
    {
        return [
            self::TYPE_DP => 'DP',
            self::TYPE_SETTLEMENT => 'Pelunasan',
            self::TYPE_ADJUSTMENT => 'Penyesuaian',
            self::TYPE_OTHER => 'Lain-lain',
        ];
    }

    /** @return array<string, string> */
    public static function typesFor(bool $isHaji = false): array
    {
        $types = self::baseTypes();

        if ($isHaji) {
            $types = [
                self::TYPE_DP => 'DP',
                self::TYPE_PORSI => 'Porsi',
                self::TYPE_SETTLEMENT => 'Pelunasan',
                self::TYPE_ADJUSTMENT => 'Penyesuaian',
                self::TYPE_OTHER => 'Lain-lain',
            ];
        }

        return $types;
    }

    public const TYPES = [
        self::TYPE_DP => 'DP',
        self::TYPE_PORSI => 'Porsi',
        self::TYPE_SETTLEMENT => 'Pelunasan',
        self::TYPE_ADJUSTMENT => 'Penyesuaian',
        self::TYPE_OTHER => 'Lain-lain',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'date',
            'invoice_created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $transaction): void {
            $transaction->pilgrim?->refreshPaymentSummary();
        });

        static::deleted(function (self $transaction): void {
            app(\App\Services\PaymentProofStore::class)->delete($transaction->proof_path);
            $transaction->pilgrim?->refreshPaymentSummary();
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ym').'-';
        $last = self::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function pilgrim(): BelongsTo
    {
        return $this->belongsTo(Pilgrim::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function formattedAmount(): string
    {
        return 'Rp '.number_format((int) $this->amount, 0, ',', '.');
    }

    public function hasProof(): bool
    {
        return filled($this->proof_path);
    }

    public function proofUrl(): ?string
    {
        return $this->proof_path;
    }

    public function hasInvoice(): bool
    {
        return filled($this->invoice_number);
    }

    public function invoiceLabel(): string
    {
        return $this->invoice_number ?? '';
    }
}
