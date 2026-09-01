<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Support\SiteProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'kind',
    'source',
    'name',
    'phone',
    'email',
    'city',
    'pax',
    'budget',
    'notes',
    'package_id',
    'pic_id',
    'status',
    'sold_pax',
    'sold_amount',
    'closed_at',
    'seats_applied',
])]
class Inquiry extends Model
{
    use RecordsActivity, SoftDeletes;

    public const STATUS_NEW = 'baru';

    public const STATUS_FOLLOWED_UP = 'dihubungi';

    public const STATUS_SOLD = 'terjual';

    public const STATUS_LOST = 'batal';

    public const SOURCE_WEBSITE = 'website';

    public const SOURCE_TEAM = 'tim';

    public const KINDS = [
        'daftar' => 'Pendaftaran',
        'tanya' => 'Tanya paket',
        'tabungan' => 'Tabungan umroh',
    ];

    public const SOURCES = [
        'website' => 'Website',
        'tim' => 'Input tim',
    ];

    public const STATUSES = [
        'baru' => 'Baru',
        'dihubungi' => 'Dihubungi',
        'terjual' => 'Closing',
        'batal' => 'Batal',
        'selesai' => 'Selesai',
    ];

    public const OPEN_STATUSES = ['baru', 'dihubungi'];

    protected function activityIgnoreKeys(): array
    {
        return array_merge($this->activityIgnore, ['seats_applied']);
    }

    protected function casts(): array
    {
        return [
            'pax' => 'integer',
            'sold_pax' => 'integer',
            'sold_amount' => 'integer',
            'closed_at' => 'datetime',
            'seats_applied' => 'boolean',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id')->withTrashed();
    }

    /**
     * @param  Builder<Inquiry>  $query
     * @return Builder<Inquiry>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isStaff()) {
            return $query->where('pic_id', $user->id);
        }

        return $query;
    }

    public function isVisibleTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return ! $user->isStaff() || (int) $this->pic_id === (int) $user->id;
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(InquiryFollowUp::class)->latest();
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function picName(): string
    {
        return $this->pic?->name ?? 'Belum ada PIC';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isSold(): bool
    {
        return $this->status === self::STATUS_SOLD;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function soldPaxCount(): int
    {
        return max(1, (int) ($this->sold_pax ?: $this->pax ?: 1));
    }

    public function formattedSoldAmount(): string
    {
        return 'Rp '.number_format((int) $this->sold_amount, 0, ',', '.');
    }

    public function whatsappUrl(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone) ?: '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        $message = 'Halo '.$this->name.', saya dari '.SiteProfile::current()->name.' terkait pengajuan paket Anda.';

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }

    public function syncPackageSeats(): void
    {
        $package = $this->package;
        if (! $package) {
            return;
        }

        $pax = $this->soldPaxCount();

        if ($this->isSold() && ! $this->seats_applied) {
            $taken = min($pax, max(0, (int) $package->seats_left));
            if ($taken > 0) {
                $package->decrement('seats_left', $taken);
            }
            $package->refresh();
            if ($package->seats_left <= 0 && $package->status === 'published') {
                $package->update(['status' => 'full']);
            }
            $this->forceFill(['seats_applied' => true])->saveQuietly();
        }

        if (! $this->isSold() && $this->seats_applied) {
            $package->increment('seats_left', $pax);
            $package->refresh();
            if ($package->status === 'full' && $package->seats_left > 0) {
                $package->update(['status' => 'published']);
            }
            $this->forceFill(['seats_applied' => false])->saveQuietly();
        }
    }
}
