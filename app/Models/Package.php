<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Support\HomeDisplay;
use App\Support\WaMessages;
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
    'price_quad',
    'price_triple',
    'price_double',
    'price_double_plus',
    'original_price',
    'price_note',
    'hotel_makkah',
    'hotel_madinah',
    'hotel_transit',
    'hotel_maktab',
    'hotel_stars',
    'airline',
    'room_type',
    'seats_total',
    'seats_left',
    'facilities',
    'exclusions',
    'itinerary',
    'description',
    'images',
    'is_featured',
    'home_sort',
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

    public const HAJI_ROOM_TYPES = [
        'double_plus' => 'Double Plus',
    ];

    /** @var array<string, int> */
    public const ROOM_OCCUPANCY = [
        'quad' => 4,
        'triple' => 3,
        'double' => 2,
        'double_plus' => 2,
    ];

    public const STATUSES = [
        'published' => 'Tayang',
        'fullbook' => 'Fullbook',
        'draft' => 'Draft',
        'hide' => 'Sembunyi',
    ];

    /** @var list<string> */
    public const CATALOG_STATUSES = ['published', 'fullbook'];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'facilities' => 'array',
            'exclusions' => 'array',
            'is_featured' => 'boolean',
            'home_sort' => 'integer',
            'is_hot' => 'boolean',
            'price' => 'integer',
            'price_quad' => 'integer',
            'price_triple' => 'integer',
            'price_double' => 'integer',
            'price_double_plus' => 'integer',
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

            $package->syncPrimaryPrice();
        });
    }

    public function syncPrimaryPrice(): void
    {
        $filled = [];

        foreach (array_keys($this->roomTypes()) as $key) {
            $value = $this->getAttribute('price_'.$key);
            if ($value !== null && (int) $value > 0) {
                $filled[$key] = (int) $value;
                $this->setAttribute('price_'.$key, (int) $value);
            } else {
                $this->setAttribute('price_'.$key, null);
            }
        }

        if ($filled === []) {
            $this->price = 0;

            return;
        }

        $this->price = min($filled);

        foreach (array_keys($this->roomTypes()) as $key) {
            if (isset($filled[$key])) {
                $this->room_type = $key;
                break;
            }
        }
    }

    public function isHaji(): bool
    {
        return in_array($this->type, self::HAJI_TYPES, true);
    }

    /**
     * Default values for operational departure forms sourced from catalog data.
     *
     * @return array<string, string|null>
     */
    public function departureDefaults(): array
    {
        return [
            'program_name' => $this->title,
            'program_kind' => $this->isHaji() ? 'haji' : 'umroh',
            'departure_date' => $this->departure_date?->format('Y-m-d'),
            'airline' => $this->airline,
            'hotel_makkah' => $this->hotel_makkah,
            'hotel_madinah' => $this->hotel_madinah,
            'hotel_transit' => $this->hotel_transit,
            'hotel_maktab' => $this->hotel_maktab,
        ];
    }

    /** @return array<string, string> */
    public function roomTypes(): array
    {
        if ($this->isHaji()) {
            return self::ROOM_TYPES + self::HAJI_ROOM_TYPES;
        }

        return self::ROOM_TYPES;
    }

    /** @return list<string> */
    public static function hajiTypeKeys(): array
    {
        return self::HAJI_TYPES;
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeVisibleOnCatalog(Builder $query): Builder
    {
        return $query->whereIn('status', self::CATALOG_STATUSES);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeDisplayedOnHome(Builder $query): Builder
    {
        $limit = HomeDisplay::packageLimit();

        return $query->visibleOnCatalog()->featured()->whereBetween('home_sort', [1, $limit]);
    }

    public static function homeLimit(): int
    {
        return HomeDisplay::packageLimit();
    }

    /**
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function homeItemsForAdmin(): \Illuminate\Support\Collection
    {
        return static::query()
            ->displayedOnHome()
            ->orderBy('home_sort')
            ->orderByDesc('id')
            ->get();
    }

    public static function displayedHomeCount(?int $exceptId = null): int
    {
        return static::query()
            ->displayedOnHome()
            ->when($exceptId, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->count();
    }

    public static function canAddToHome(?int $exceptId = null): bool
    {
        return static::displayedHomeCount($exceptId) < self::homeLimit();
    }

    /**
     * @param  list<int>  $ids
     */
    public static function applyHomeOrder(array $ids): void
    {
        $limit = self::homeLimit();
        $activeIds = array_slice(array_map('intval', $ids), 0, $limit);

        foreach ($activeIds as $index => $id) {
            static::query()->whereKey($id)->update([
                'is_featured' => true,
                'home_sort' => $index + 1,
            ]);
        }

        static::query()
            ->featured()
            ->when($activeIds !== [], fn (Builder $q) => $q->whereNotIn('id', $activeIds))
            ->update(['is_featured' => false, 'home_sort' => null]);
    }

    public static function nextAvailableHomeSlot(?int $exceptId = null): ?int
    {
        $limit = self::homeLimit();
        $used = static::query()
            ->displayedOnHome()
            ->when($exceptId, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->pluck('home_sort')
            ->map(fn ($value) => (int) $value)
            ->all();

        for ($slot = 1; $slot <= $limit; $slot++) {
            if (! in_array($slot, $used, true)) {
                return $slot;
            }
        }

        return null;
    }

    public function scopeNeedsFlyer(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->whereNull('images')
                ->orWhere('images', '[]')
                ->orWhere('images', 'null');
        });
    }

    public function scopeDataComplete(Builder $query): Builder
    {
        return $query->whereNotNull('departure_date')->where(function (Builder $builder) {
            $builder->whereNotNull('images')
                ->where('images', '!=', '[]')
                ->where('images', '!=', 'null');
        });
    }

    public function scopeDataIncomplete(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->whereNull('departure_date')
                ->orWhereNull('images')
                ->orWhere('images', '[]')
                ->orWhere('images', 'null');
        });
    }

    public function needsFlyer(): bool
    {
        return ($this->images ?? []) === [];
    }

    public function isDataComplete(): bool
    {
        return $this->departure_date !== null && ! $this->needsFlyer();
    }

    public function isFullbook(): bool
    {
        return $this->status === 'fullbook';
    }

    public function isVisibleOnCatalog(): bool
    {
        return in_array($this->status, self::CATALOG_STATUSES, true);
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
        return $this->roomTypeLabel($this->room_type);
    }

    public function roomTypeLabel(string $key): string
    {
        $name = $this->roomTypes()[$key] ?? self::ROOM_TYPES[$key] ?? self::HAJI_ROOM_TYPES[$key] ?? $key;
        $pax = self::ROOM_OCCUPANCY[$key] ?? null;

        return $pax ? "{$name} ({$pax} org/kamar)" : $name;
    }

    public function roomOccupancyLabel(string $key): string
    {
        $pax = self::ROOM_OCCUPANCY[$key] ?? null;

        return $pax ? "{$pax} org/kamar" : '';
    }

    /**
     * @return list<array{key: string, label: string, occupancy: int, occupancy_label: string, full_label: string, price: int}>
     */
    public function roomPriceList(): array
    {
        $rows = [];

        foreach ($this->roomTypes() as $key => $label) {
            $value = $this->{'price_'.$key};
            if ($value) {
                $occupancy = self::ROOM_OCCUPANCY[$key] ?? 0;
                $rows[] = [
                    'key' => $key,
                    'label' => $label,
                    'occupancy' => $occupancy,
                    'occupancy_label' => $this->roomOccupancyLabel($key),
                    'full_label' => $this->roomTypeLabel($key),
                    'price' => (int) $value,
                ];
            }
        }

        return $rows;
    }

    public function roomRangeLabel(): string
    {
        $rows = $this->roomPriceList();
        if ($rows !== []) {
            return collect($rows)->pluck('label')->implode(' · ');
        }

        return $this->roomTypes()[$this->room_type] ?? self::ROOM_TYPES[$this->room_type] ?? $this->room_type;
    }

    public function startingRoomLabel(): ?string
    {
        $rows = $this->roomPriceList();
        if ($rows === []) {
            return null;
        }

        $cheapest = collect($rows)->sortBy('price')->first();

        return strtolower($cheapest['label']);
    }

    public function hasMultipleRoomPrices(): bool
    {
        return count($this->roomPriceList()) > 1;
    }

    public function formattedMoney(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
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
        return $this->formattedMoney((int) $this->price);
    }

    public function formattedStartingPrice(): string
    {
        $prefix = count($this->roomPriceList()) > 1 ? 'Mulai ' : '';

        return $prefix.$this->formattedPrice();
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
        return WaMessages::packageInquiry($this);
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
