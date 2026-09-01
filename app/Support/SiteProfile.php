<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class SiteProfile
{
    public const DEFAULT_NAME = 'SafarHaramin';

    public const DEFAULT_TAGLINE = 'Travel haji dan umroh. Pilih paket dari katalog, cek seat, lanjut WhatsApp.';

    public const DEFAULT_TITLE_SUFFIX = 'Travel Haji & Umroh';

    public const DEFAULT_LOGO = '/images/logo.webp';

    public const DEFAULT_WA = '6281234567890';

    public function __construct(
        public string $name,
        public string $tagline,
        public string $titleSuffix,
        public string $logoUrl,
        public string $waNumber,
    ) {}

    public static function current(): self
    {
        try {
            if (! Schema::hasTable('settings')) {
                return self::defaults();
            }

            $logo = Setting::getValue('site_logo');

            return new self(
                self::resolveName(Setting::getValue('site_name')),
                Setting::getValue('site_tagline', self::DEFAULT_TAGLINE) ?: self::DEFAULT_TAGLINE,
                Setting::getValue('site_title_suffix', self::DEFAULT_TITLE_SUFFIX) ?: self::DEFAULT_TITLE_SUFFIX,
                $logo !== '' ? $logo : self::DEFAULT_LOGO,
                Setting::getValue('wa_number', self::DEFAULT_WA) ?: self::DEFAULT_WA,
            );
        } catch (\Throwable) {
            return self::defaults();
        }
    }

    public static function defaults(): self
    {
        return new self(
            self::resolveName(''),
            self::DEFAULT_TAGLINE,
            self::DEFAULT_TITLE_SUFFIX,
            self::DEFAULT_LOGO,
            self::DEFAULT_WA,
        );
    }

    private static function resolveName(string $fromSettings): string
    {
        if ($fromSettings !== '') {
            return $fromSettings;
        }

        $appName = (string) config('app.name');
        if ($appName !== '' && $appName !== 'Laravel') {
            return $appName;
        }

        return self::DEFAULT_NAME;
    }
}
