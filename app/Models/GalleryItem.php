<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Support\HomeDisplay;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[Fillable(['title', 'image', 'caption', 'category', 'group_name', 'sort_order', 'show_on_home', 'home_sort'])]
class GalleryItem extends Model
{
    use RecordsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'show_on_home' => 'boolean',
            'home_sort' => 'integer',
        ];
    }

    public const CATEGORY_UMROH = 'umroh';

    public const CATEGORY_HAJI = 'haji';

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_UMROH => 'Umroh',
            self::CATEGORY_HAJI => 'Haji',
        ];
    }

    public function categoryLabel(): string
    {
        return self::categories()[$this->category] ?? ucfirst((string) $this->category);
    }

    public function groupLabel(): string
    {
        return $this->group_name !== null && $this->group_name !== ''
            ? $this->group_name
            : 'Lainnya';
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeForHome(Builder $query): Builder
    {
        return $query->where('show_on_home', true);
    }

    public function scopeDisplayedOnHome(Builder $query): Builder
    {
        $limit = HomeDisplay::galleryLimit();

        return $query->forHome()->whereBetween('home_sort', [1, $limit]);
    }

    public static function homeLimit(): int
    {
        return HomeDisplay::galleryLimit();
    }

    /**
     * @return list<int>
     */
    public static function usedHomeSlots(?int $exceptId = null): array
    {
        return static::query()
            ->forHome()
            ->where('home_sort', '>', 0)
            ->when($exceptId, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->pluck('home_sort')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function homeSortOptions(): array
    {
        $options = [0 => 0];
        $used = static::usedHomeSlots($this->id);
        $current = (int) ($this->home_sort ?? 0);

        for ($slot = 1; $slot <= self::homeLimit(); $slot++) {
            if (! in_array($slot, $used, true) || $current === $slot) {
                $options[$slot] = $slot;
            }
        }

        return $options;
    }

    public static function displayedOnHomeCount(?int $exceptId = null): int
    {
        return static::displayedHomeCount($exceptId);
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
     * @return Collection<int, self>
     */
    public static function homeItemsForAdmin(): Collection
    {
        return static::query()
            ->displayedOnHome()
            ->orderBy('home_sort')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
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
                'show_on_home' => true,
                'home_sort' => $index + 1,
            ]);
        }

        static::query()
            ->forHome()
            ->when($activeIds !== [], fn (Builder $q) => $q->whereNotIn('id', $activeIds))
            ->update(['show_on_home' => false, 'home_sort' => null]);
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

    /**
     * @param  list<int>  $ids
     */
    public static function applyGalleryOrder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            static::query()->whereKey($id)->update(['sort_order' => $index]);
        }
    }

    /**
     * @return array<string, Collection<string, Collection<int, self>>>
     */
    public static function groupedForStorefront(): array
    {
        $items = static::query()->orderBy('sort_order')->orderByDesc('id')->get();
        $grouped = [];

        foreach (array_keys(self::categories()) as $category) {
            $grouped[$category] = $items
                ->where('category', $category)
                ->groupBy(fn (self $item) => $item->groupLabel());
        }

        return $grouped;
    }
}
