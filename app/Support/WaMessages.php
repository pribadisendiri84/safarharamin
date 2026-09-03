<?php

namespace App\Support;

use App\Models\City;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Setting;

class WaMessages
{
    public const KEY_FLOAT = 'wa_msg_float';

    public const KEY_FLOAT_LABEL = 'wa_float_label';

    public const KEY_FLOAT_ENABLED = 'wa_float_enabled';

    public const KEY_PACKAGE = 'wa_msg_package';

    public const KEY_REGISTER = 'wa_msg_register';

    public const KEY_INQUIRY_REPLY = 'wa_msg_inquiry_reply';

    public const DEFAULT_FLOAT = 'Halo {site}, saya ingin tanya paket haji/umroh.';

    public const DEFAULT_FLOAT_LABEL = 'Tanya via WhatsApp';

    public const DEFAULT_PACKAGE = 'Halo {site}, saya tertarik paket {package_title} ({package_price}, {package_duration} hari, berangkat {package_departure}). Mohon info seat & cara daftar.

Nama: {name}
WA: {phone}';

    public const DEFAULT_REGISTER = 'Halo {site}, saya {name} ingin daftar{package_part} ({pax} jamaah) dari {city}. Mohon dihubungi.';

    public const DEFAULT_INQUIRY_REPLY = 'Halo {name}, saya dari {site} terkait pengajuan paket Anda.';

    public static function float(): string
    {
        return self::render(self::KEY_FLOAT, self::DEFAULT_FLOAT, [
            'site' => SiteProfile::current()->name,
        ]);
    }

    public static function floatLabel(): string
    {
        return self::template(self::KEY_FLOAT_LABEL, self::DEFAULT_FLOAT_LABEL);
    }

    public static function floatEnabled(): bool
    {
        $value = Setting::getValue(self::KEY_FLOAT_ENABLED, '1');

        return $value === '' || $value === '1';
    }

    /**
     * @return array{enabled: bool, label: string}
     */
    public static function floatButton(): array
    {
        return [
            'enabled' => self::floatEnabled(),
            'label' => self::floatLabel(),
        ];
    }

    public static function packageInquiry(Package $package, string $name = '', string $phone = ''): string
    {
        $rooms = collect($package->roomPriceList())
            ->map(fn (array $row) => $row['full_label'].' '.$package->formattedMoney($row['price']).'/jamaah')
            ->implode(', ');
        $pricePart = $rooms !== '' ? $rooms : $package->formattedStartingPrice();

        return self::cleanupEmptyLines(self::render(self::KEY_PACKAGE, self::DEFAULT_PACKAGE, [
            'site' => SiteProfile::current()->name,
            'package_title' => $package->title,
            'package_price' => $pricePart,
            'package_duration' => (string) $package->duration_days,
            'package_departure' => $package->departureLine(),
            'name' => $name,
            'phone' => $phone,
        ]));
    }

    public static function register(Inquiry $inquiry, ?Package $package = null): string
    {
        $packagePart = $package ? ' paket '.$package->title : '';

        return self::render(self::KEY_REGISTER, self::DEFAULT_REGISTER, [
            'site' => SiteProfile::current()->name,
            'name' => $inquiry->name,
            'package_part' => $packagePart,
            'pax' => (string) $inquiry->pax,
            'city' => City::label($inquiry->city),
        ]);
    }

    public static function inquiryReply(Inquiry $inquiry): string
    {
        return self::render(self::KEY_INQUIRY_REPLY, self::DEFAULT_INQUIRY_REPLY, [
            'site' => SiteProfile::current()->name,
            'name' => $inquiry->name,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function adminTemplates(): array
    {
        return [
            self::KEY_FLOAT_ENABLED => self::floatEnabled() ? '1' : '0',
            self::KEY_FLOAT_LABEL => self::floatLabel(),
            self::KEY_FLOAT => self::template(self::KEY_FLOAT, self::DEFAULT_FLOAT),
            self::KEY_PACKAGE => self::template(self::KEY_PACKAGE, self::DEFAULT_PACKAGE),
            self::KEY_REGISTER => self::template(self::KEY_REGISTER, self::DEFAULT_REGISTER),
            self::KEY_INQUIRY_REPLY => self::template(self::KEY_INQUIRY_REPLY, self::DEFAULT_INQUIRY_REPLY),
        ];
    }

    private static function template(string $key, string $default): string
    {
        $value = Setting::getValue($key, $default);

        return $value !== '' ? $value : $default;
    }

    /**
     * @param  array<string, string>  $vars
     */
    private static function render(string $key, string $default, array $vars): string
    {
        $template = self::template($key, $default);

        $replacements = [];
        foreach ($vars as $name => $value) {
            $replacements['{'.$name.'}'] = $value;
        }

        return strtr($template, $replacements);
    }

    private static function cleanupEmptyLines(string $text): string
    {
        $lines = array_filter(
            explode("\n", $text),
            fn (string $line) => ! preg_match('/^.+:\s*$/', trim($line)),
        );

        return implode("\n", $lines);
    }
}
