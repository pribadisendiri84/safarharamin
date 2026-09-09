<?php

namespace App\Support;

class HomeSeo
{
    public static function pageTitle(): string
    {
        return CompanyProfile::BRAND_NAME.' | '.CompanyProfile::LEGAL_NAME;
    }

    public static function metaDescription(): string
    {
        return CompanyProfile::BRAND_NAME.' — penyelenggara perjalanan ibadah Umrah dan Haji Khusus oleh '
            .CompanyProfile::LEGAL_NAME.'. Lihat paket, cek seat dan harga, daftar via WhatsApp.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function structuredData(SiteProfile $site): array
    {
        $organizationId = url('/#organization');
        $websiteId = url('/#website');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $organizationId,
                    'name' => CompanyProfile::BRAND_NAME,
                    'legalName' => CompanyProfile::LEGAL_NAME,
                    'url' => url('/'),
                    'logo' => url($site->logoUrl),
                    'foundingDate' => (string) CompanyProfile::FOUNDED_YEAR,
                    'description' => self::metaDescription(),
                    'sameAs' => [CompanyProfile::OFFICIAL_PROFILE_URL],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $websiteId,
                    'url' => url('/'),
                    'name' => $site->name,
                    'description' => self::metaDescription(),
                    'publisher' => ['@id' => $organizationId],
                ],
            ],
        ];
    }
}
