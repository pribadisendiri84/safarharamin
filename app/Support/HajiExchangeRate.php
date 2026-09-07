<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class HajiExchangeRate
{
    public const KEY_ENABLED = 'haji_exchange_rate_enabled';

    public const KEY_MODE = 'haji_exchange_rate_mode';

    public const KEY_RATE = 'haji_exchange_rate';

    public const KEY_CURRENCY = 'haji_exchange_rate_currency';

    public const KEY_UPDATED_AT = 'haji_exchange_rate_updated_at';

    public const MODE_MANUAL = 'manual';

    public const MODE_AUTO = 'auto';

    public const DEFAULT_CURRENCY = 'SAR';

    public static function enabled(): bool
    {
        if (! Schema::hasTable('settings')) {
            return false;
        }

        return Setting::getValue(self::KEY_ENABLED, '1') === '1';
    }

    /**
     * @return array{enabled: bool, mode: string, currency: string, rate: int|null, formatted: string|null, updated_at: string|null, source: string|null}|null
     */
    public static function display(): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        self::refreshIfStale();

        $rate = self::storedRate();
        if ($rate === null || $rate <= 0) {
            return null;
        }

        $mode = self::mode();
        $updatedAt = Setting::getValue(self::KEY_UPDATED_AT);

        return [
            'enabled' => true,
            'mode' => $mode,
            'currency' => self::currency(),
            'rate' => $rate,
            'formatted' => self::format($rate),
            'updated_at' => $updatedAt !== '' ? $updatedAt : null,
            'source' => $mode === self::MODE_AUTO ? 'Pembaruan otomatis' : 'Input admin',
        ];
    }

    public static function mode(): string
    {
        $mode = Setting::getValue(self::KEY_MODE, self::MODE_MANUAL);

        return in_array($mode, [self::MODE_MANUAL, self::MODE_AUTO], true) ? $mode : self::MODE_MANUAL;
    }

    public static function currency(): string
    {
        $currency = strtoupper(trim(Setting::getValue(self::KEY_CURRENCY, self::DEFAULT_CURRENCY)));

        return $currency !== '' ? $currency : self::DEFAULT_CURRENCY;
    }

    public static function storedRate(): ?int
    {
        $raw = Setting::getValue(self::KEY_RATE);
        if ($raw === '' || ! is_numeric($raw)) {
            return null;
        }

        return (int) round((float) $raw);
    }

    public static function format(int $rate): string
    {
        return 'Rp '.number_format($rate, 0, ',', '.');
    }

    /**
     * @return array{enabled: string, mode: string, currency: string, rate: string, updated_at: string}
     */
    public static function adminForm(): array
    {
        return [
            'enabled' => Setting::getValue(self::KEY_ENABLED, '1'),
            'mode' => self::mode(),
            'currency' => self::currency(),
            'rate' => Setting::getValue(self::KEY_RATE),
            'updated_at' => Setting::getValue(self::KEY_UPDATED_AT),
        ];
    }

    public static function saveManual(int $rate, string $currency, bool $enabled, string $mode): void
    {
        Setting::setValue(self::KEY_ENABLED, $enabled ? '1' : '0');
        Setting::setValue(self::KEY_MODE, $mode === self::MODE_AUTO ? self::MODE_AUTO : self::MODE_MANUAL);
        Setting::setValue(self::KEY_CURRENCY, strtoupper(trim($currency)) ?: self::DEFAULT_CURRENCY);

        if ($mode === self::MODE_MANUAL) {
            Setting::setValue(self::KEY_RATE, (string) $rate);
            Setting::setValue(self::KEY_UPDATED_AT, now()->toIso8601String());
        }
    }

    public static function refreshIfStale(): void
    {
        if (self::mode() !== self::MODE_AUTO) {
            return;
        }

        $updatedAt = Setting::getValue(self::KEY_UPDATED_AT);
        if ($updatedAt !== '') {
            try {
                $last = \Carbon\Carbon::parse($updatedAt);
                if ($last->diffInHours(now()) < 6) {
                    return;
                }
            } catch (\Throwable) {
                //
            }
        }

        self::refreshFromApi();
    }

    public static function refreshFromApi(): bool
    {
        $currency = self::currency();

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get('https://open.er-api.com/v6/latest/'.$currency);

            if (! $response->successful()) {
                return false;
            }

            $rate = $response->json('rates.IDR');
            if (! is_numeric($rate) || (float) $rate <= 0) {
                return false;
            }

            Setting::setValue(self::KEY_RATE, (string) (int) round((float) $rate));
            Setting::setValue(self::KEY_UPDATED_AT, now()->toIso8601String());

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
