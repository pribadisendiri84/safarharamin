<?php

namespace App\Support;

use App\Models\Package;

class PackageCardBadge
{
    public const PRESET_HARGA_ESTIMASI = 'harga_estimasi';

    public const PRESET_BEST_SELLER = 'best_seller';

    public const PRESET_PAKET_FAVORIT = 'paket_favorit';

    public const PRESET_PROMO = 'promo';

    public const PRESET_KUOTA_TERBATAS = 'kuota_terbatas';

    public const PRESET_CUSTOM = 'custom';

    public const ICON_MONEY = 'money';

    public const ICON_STAR = 'star';

    public const ICON_FIRE = 'fire';

    public const ICON_HOURGLASS = 'hourglass';

    public const ICON_SPARKLE = 'sparkle';

    public const ICON_KAABA = 'kaaba';

    public const ICON_CALENDAR = 'calendar';

    public const ICON_NONE = 'none';

    public const POSITION_TOP_LEFT = 'top_left';

    public const POSITION_TOP_RIGHT = 'top_right';

    public const POSITION_BOTTOM_LEFT = 'bottom_left';

    public const POSITION_BOTTOM_RIGHT = 'bottom_right';

    public const PRESET_DEFAULT = self::PRESET_KUOTA_TERBATAS;

    public const ICON_DEFAULT = self::ICON_HOURGLASS;

    public const POSITION_DEFAULT = self::POSITION_TOP_LEFT;

    /**
     * @return array<string, string>
     */
    public static function presets(): array
    {
        return [
            self::PRESET_HARGA_ESTIMASI => 'Harga Estimasi',
            self::PRESET_BEST_SELLER => 'Best Seller',
            self::PRESET_PAKET_FAVORIT => 'Paket Favorit',
            self::PRESET_PROMO => 'Promo',
            self::PRESET_KUOTA_TERBATAS => 'Kuota Terbatas',
            self::PRESET_CUSTOM => 'Custom',
        ];
    }

    /**
     * @return array<string, array{label: string, class: ?string, emoji: ?string}>
     */
    public static function icons(): array
    {
        return [
            self::ICON_MONEY => ['label' => '💰', 'class' => 'bi-cash-coin', 'emoji' => null],
            self::ICON_STAR => ['label' => '⭐', 'class' => 'bi-star-fill', 'emoji' => null],
            self::ICON_FIRE => ['label' => '🔥', 'class' => 'bi-fire', 'emoji' => null],
            self::ICON_HOURGLASS => ['label' => '⏳', 'class' => 'bi-hourglass-split', 'emoji' => null],
            self::ICON_SPARKLE => ['label' => '✦', 'class' => 'bi-stars', 'emoji' => null],
            self::ICON_KAABA => ['label' => '🕋', 'class' => null, 'emoji' => '🕋'],
            self::ICON_CALENDAR => ['label' => '📅', 'class' => 'bi-calendar3', 'emoji' => null],
            self::ICON_NONE => ['label' => 'None', 'class' => null, 'emoji' => null],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function positions(): array
    {
        return [
            self::POSITION_TOP_LEFT => 'Top Left',
            self::POSITION_TOP_RIGHT => 'Top Right',
            self::POSITION_BOTTOM_LEFT => 'Bottom Left',
            self::POSITION_BOTTOM_RIGHT => 'Bottom Right',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function presetDefaultIcons(): array
    {
        return [
            self::PRESET_HARGA_ESTIMASI => self::ICON_MONEY,
            self::PRESET_BEST_SELLER => self::ICON_STAR,
            self::PRESET_PAKET_FAVORIT => self::ICON_SPARKLE,
            self::PRESET_PROMO => self::ICON_FIRE,
            self::PRESET_KUOTA_TERBATAS => self::ICON_HOURGLASS,
            self::PRESET_CUSTOM => self::ICON_NONE,
        ];
    }

    public static function presetLabel(string $preset): string
    {
        return self::presets()[$preset] ?? '';
    }

    public static function normalizePreset(?string $preset): string
    {
        return array_key_exists((string) $preset, self::presets())
            ? (string) $preset
            : self::PRESET_DEFAULT;
    }

    public static function normalizeIcon(?string $icon): string
    {
        return array_key_exists((string) $icon, self::icons())
            ? (string) $icon
            : self::ICON_DEFAULT;
    }

    public static function normalizePosition(?string $position): string
    {
        return array_key_exists((string) $position, self::positions())
            ? (string) $position
            : self::POSITION_DEFAULT;
    }

    public static function resolveText(Package $package): string
    {
        $preset = self::normalizePreset($package->card_badge_preset);
        $custom = trim((string) ($package->card_badge_text ?? ''));

        if ($preset === self::PRESET_CUSTOM) {
            return $custom;
        }

        return $custom !== '' ? $custom : self::presetLabel($preset);
    }

    public static function resolveIcon(Package $package): string
    {
        return self::normalizeIcon($package->card_badge_icon);
    }

    public static function iconClass(string $icon): ?string
    {
        return self::icons()[self::normalizeIcon($icon)]['class'] ?? null;
    }

    public static function iconEmoji(string $icon): ?string
    {
        return self::icons()[self::normalizeIcon($icon)]['emoji'] ?? null;
    }

    public static function positionClass(string $position): string
    {
        return 'is-'.str_replace('_', '-', self::normalizePosition($position));
    }
}
