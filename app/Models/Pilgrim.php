<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\RoomType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'departure_id',
    'inquiry_id',
    'room_id',
    'full_name',
    'phone',
    'gender',
    'room_type',
    'haji_registration_id',
    'haji_portion_number',
    'package_price',
    'paid_amount',
    'dp_date',
    'settlement_date',
    'notes',
])]
class Pilgrim extends Model
{
    use RecordsActivity, SoftDeletes;

    public const GENDERS = [
        'male' => 'Laki-laki',
        'female' => 'Perempuan',
    ];

    protected function casts(): array
    {
        return [
            'package_price' => 'integer',
            'paid_amount' => 'integer',
            'dp_date' => 'date',
            'settlement_date' => 'date',
        ];
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PilgrimTransaction::class)->latest('paid_at');
    }

    public function roomTypeEnum(): RoomType
    {
        return RoomType::fromValue($this->room_type);
    }

    public function roomTypeLabel(): string
    {
        return $this->roomTypeEnum()->label();
    }

    public function genderLabel(): string
    {
        return self::GENDERS[$this->gender] ?? '-';
    }

    public function isGrouped(): bool
    {
        return $this->room_id !== null;
    }

    public function groupingStatusLabel(): string
    {
        return $this->isGrouped() ? 'Grouped' : 'Belum';
    }

    public function groupingStatusClass(): string
    {
        return $this->isGrouped() ? 'ok' : 'warn';
    }

    public function paymentPercent(): ?int
    {
        $price = (int) $this->package_price;
        if ($price <= 0) {
            return null;
        }

        return min(100, (int) round(((int) $this->paid_amount / $price) * 100));
    }

    public function hasOverpayment(): bool
    {
        $price = (int) $this->package_price;

        return $price > 0 && (int) $this->paid_amount > $price;
    }

    public function overpaymentAmount(): int
    {
        return max(0, (int) $this->paid_amount - (int) $this->package_price);
    }

    public function formattedOverpayment(): string
    {
        return 'Rp '.number_format($this->overpaymentAmount(), 0, ',', '.');
    }

    public function isPaidInFull(): bool
    {
        $price = (int) $this->package_price;

        return $price > 0 && (int) $this->paid_amount >= $price;
    }

    public function paymentStatusLabel(): string
    {
        if ((int) $this->package_price <= 0) {
            return '—';
        }

        if ((int) $this->paid_amount <= 0) {
            return '0%';
        }

        if ($this->hasOverpayment()) {
            return 'Lebih bayar';
        }

        if ($this->isPaidInFull()) {
            return '100%';
        }

        return $this->paymentPercent().'%';
    }

    public function paymentStatusHint(): ?string
    {
        if (! $this->hasOverpayment()) {
            return null;
        }

        return $this->formattedOverpayment();
    }

    public function paymentStatusClass(): string
    {
        if ((int) $this->package_price <= 0) {
            return 'muted';
        }

        if ($this->hasOverpayment()) {
            return 'pay-over';
        }

        if ($this->isPaidInFull()) {
            return 'ok';
        }

        if ((int) $this->paid_amount <= 0) {
            return 'warn';
        }

        return 'pay-partial';
    }

    public function remainingBalance(): int
    {
        return max(0, (int) $this->package_price - (int) $this->paid_amount);
    }

    public function formattedPackagePrice(): string
    {
        return 'Rp '.number_format((int) $this->package_price, 0, ',', '.');
    }

    public function formattedPaidAmount(): string
    {
        return 'Rp '.number_format((int) $this->paid_amount, 0, ',', '.');
    }

    public function formattedRemainingBalance(): string
    {
        return 'Rp '.number_format($this->remainingBalance(), 0, ',', '.');
    }

    public function refreshPaymentSummary(): void
    {
        $transactions = $this->transactions()->orderBy('paid_at')->get();
        $paid = (int) $transactions->sum('amount');
        $dpDate = $transactions->firstWhere('type', PilgrimTransaction::TYPE_DP)?->paid_at;
        $settlementDate = $transactions->firstWhere('type', PilgrimTransaction::TYPE_SETTLEMENT)?->paid_at;

        $this->forceFill([
            'paid_amount' => $paid,
            'dp_date' => $dpDate,
            'settlement_date' => $settlementDate,
        ])->saveQuietly();
    }
}
