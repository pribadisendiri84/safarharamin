<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Support\SiteProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'slug',
    'type',
    'departure_city',
    'departure_date',
    'duration_days',
    'price',
    'original_price',
    'hotel_makkah',
    'hotel_madinah',
    'hotel_stars',
    'airline',
    'room_type',
    'seats_total',
    'seats_left',
    'facilities',
    'itinerary',
    'description',
    'images',
    'is_featured',
    'is_hot',
    'status',
])]
class Package extends Model
{
    use RecordsActivity, SoftDeletes;

    public const TYPES = [
        'umroh' => 'Umroh Reguler',
        'umroh_plus' => 'Umroh Plus',
        'umroh_ramadhan' => 'Umroh Ramadhan',
        'haji_plus' => 'Haji Plus',
        'haji_furoda' => 'Haji Furoda',
    ];

    public const UMROH_TYPES = ['umroh', 'umroh_plus', 'umroh_ramadhan'];

    public const HAJI_TYPES = ['haji_plus', 'haji_furoda'];

    public const ROOM_TYPES = [
        'quad' => 'Quad',
        'triple' => 'Triple',
        'double' => 'Double',
    ];

    public const STATUSES = [
        'published' => 'Tayang',
        'draft' => 'Draft',
        'full' => 'Penuh',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'facilities' => 'array',
            'is_featured' => 'boolean',
            'is_hot' => 'boolean',
            'price' => 'integer',
            'original_price' => 'integer',
            'departure_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Package $package) {
            if ($package->slug === null || $package->slug === '') {
                $package->slug = static::uniqueSlug($package->title, $package->id);
            }
        });
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? Str::headline($this->type);
    }

    public function cityLabel(): string
    {
        return City::label($this->departure_city);
    }

    public function roomLabel(): string
    {
        return self::ROOM_TYPES[$this->room_type] ?? $this->room_type;
    }

    public function coverImage(): string
    {
        return ($this->images ?? [])[0] ?? '/images/placeholder-kaaba.svg';
    }

    public function gallery(): array
    {
        $images = $this->images ?? [];

        return $images !== [] ? $images : ['/images/placeholder-kaaba.svg'];
    }

    public function formattedPrice(): string
    {
        return 'Rp '.number_format($this->price, 0, ',', '.');
    }

    public function formattedOriginalPrice(): ?string
    {
        if (! $this->original_price || $this->original_price <= $this->price) {
            return null;
        }

        return 'Rp '.number_format($this->original_price, 0, ',', '.');
    }

    public function discountPercent(): ?int
    {
        if (! $this->original_price || $this->original_price <= $this->price) {
            return null;
        }

        return (int) round((($this->original_price - $this->price) / $this->original_price) * 100);
    }

    public function seatsLine(): string
    {
        return $this->seats_left.' dari '.$this->seats_total.' seat';
    }

    public function departureLine(): string
    {
        $date = $this->departure_date?->translatedFormat('d M Y') ?? 'Jadwal menyusul';

        return $date.' · '.$this->cityLabel();
    }

    public function whatsappMessage(): string
    {
        return 'Halo '.SiteProfile::current()->name.", saya tertarik paket {$this->title} ({$this->formattedPrice()}, {$this->duration_days} hari, berangkat {$this->departureLine()}). Mohon info seat & cara daftar.";
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'paket';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
