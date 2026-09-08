<?php

namespace App\Support;

use App\Models\Airline;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\Setting;

class HajiPlusPage
{
    public const KEY = 'haji_plus_page';

    /**
     * @return array{
     *   hero: array<string, mixed>,
     *   rooms: list<array<string, mixed>>,
     *   benefits: list<array<string, mixed>>,
     *   hotels: list<array<string, mixed>>,
     *   partner_airlines: list<string>,
     *   airline: array<string, mixed>,
     *   flow: list<array<string, mixed>>,
     *   cta: array<string, mixed>
     * }
     */
    public static function content(?Package $package = null): array
    {
        $defaults = self::defaults($package);
        $stored = self::stored();

        $hotels = self::resolveHotels(self::mergeHotels($defaults['hotels'], $stored['hotels'] ?? []));

        return [
            'hero' => array_replace($defaults['hero'], $stored['hero'] ?? []),
            'rooms' => self::mergeList($defaults['rooms'], $stored['rooms'] ?? [], ['key', 'label', 'occupancy', 'price_label', 'price_note', 'image', 'is_featured']),
            'benefits' => self::mergeList($defaults['benefits'], $stored['benefits'] ?? [], ['title', 'description', 'icon']),
            'hotels' => $hotels,
            'partner_airlines' => self::mergePartnerAirlines($defaults['partner_airlines'], $stored['partner_airlines'] ?? []),
            'airline' => array_replace($defaults['airline'], $stored['airline'] ?? []),
            'flow' => self::mergeList($defaults['flow'], $stored['flow'] ?? [], ['title', 'description', 'icon']),
            'cta' => array_replace($defaults['cta'], $stored['cta'] ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function stored(): array
    {
        $raw = Setting::getValue(self::KEY);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function save(array $payload): void
    {
        Setting::setValue(self::KEY, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }

    /**
     * @return array{
     *   hero: array<string, mixed>,
     *   rooms: list<array<string, mixed>>,
     *   benefits: list<array<string, mixed>>,
     *   hotels: list<array<string, mixed>>,
     *   partner_airlines: list<string>,
     *   airline: array<string, mixed>,
     *   flow: list<array<string, mixed>>,
     *   cta: array<string, mixed>
     * }
     */
    public static function defaults(?Package $package = null): array
    {
        $package ??= HajiPlusProgram::primary();
        $rooms = HajiPlusProgram::roomOptions($package);
        $hotels = HajiPlusProgram::hotelShowcase($package);

        return [
            'hero' => [
                'badge' => 'Haji Khusus',
                'season' => HajiPlusProgram::SEASON,
                'title' => HajiPlusProgram::HERO_TITLE,
                'subtitle' => HajiPlusProgram::HERO_SUBTITLE,
                'starting_price' => '',
                'show_quota' => HajiPlusProgram::showLimitedQuota($package) ? '1' : '0',
                'image' => 'https://images.unsplash.com/photo-1564769625905-50e93615e769?w=1600&q=80',
            ],
            'rooms' => array_map(function (array $room) {
                return [
                    'key' => $room['key'],
                    'label' => $room['label'],
                    'occupancy' => str_replace('org/kamar', 'orang', $room['occupancy_label']),
                    'price_label' => $room['formatted_price_short'] ?: $room['price_usd'],
                    'price_note' => '/pax',
                    'image' => HajiPlusProgram::roomImage($room['key']),
                    'is_featured' => $room['key'] === HajiPlusProgram::featuredRoomKey() ? '1' : '0',
                ];
            }, $rooms),
            'benefits' => array_map(fn (array $item) => [
                'title' => $item['title'],
                'description' => $item['description'],
                'icon' => $item['icon'],
            ], HajiPlusProgram::benefits()),
            'hotels' => [
                self::hotelRow(
                    source: 'custom',
                    city: $hotels[0]['city'] ?? 'Madinah',
                    title: 'Hotel Madinah',
                    distance: $hotels[0]['distance'] ?? '±100 m dari Masjid Nabawi',
                    featuresText: "Al Ansar / setara\nLokasi strategis, akses mudah",
                    image: $hotels[0]['image'] ?? '',
                ),
                self::hotelRow(
                    source: 'custom',
                    city: $hotels[1]['city'] ?? 'Makkah',
                    title: 'Hotel Makkah',
                    distance: $hotels[1]['distance'] ?? '±100 m dari Masjidil Haram',
                    featuresText: "Dar Al Eiman / setara\nLokasi strategis, akses mudah",
                    image: $hotels[1]['image'] ?? '',
                ),
            ],
            'partner_airlines' => array_values(array_map(
                fn (array $airline) => $airline['name'],
                HajiPlusProgram::partnerAirlines($package),
            )),
            'airline' => [
                'title' => HajiPlusProgram::airlineLabel($package),
                'description' => 'Penerbangan langsung dengan layanan maskapai terpercaya untuk perjalanan ibadah yang nyaman.',
                'image' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=900&q=80',
                'points_text' => "Jadwal penerbangan fleksibel\nLayanan bagasi sesuai program\nKabin nyaman untuk perjalanan jauh",
            ],
            'flow' => [
                ['title' => 'Konsultasi', 'description' => 'Diskusi kebutuhan & pilih tipe kamar terbaik.', 'icon' => 'bi-chat-dots'],
                ['title' => 'Pendaftaran', 'description' => 'Lengkapi data dan persyaratan awal.', 'icon' => 'bi-file-earmark-text'],
                ['title' => 'Pembayaran', 'description' => 'Lakukan setoran awal sesuai ketentuan.', 'icon' => 'bi-wallet2'],
                ['title' => 'Pelunasan & berangkat', 'description' => 'Proses akhir hingga jadwal keberangkatan.', 'icon' => 'bi-airplane'],
            ],
            'cta' => [
                'title' => 'Siap berangkat ke Tanah Suci?',
                'description' => 'Konsultasikan tipe kamar dan jadwal keberangkatan dengan tim kami.',
                'note' => 'Tim kami siap membantu 24/7',
            ],
        ];
    }

    /**
     * @return array{prefix: string, amount: string, unit: string}
     */
    public static function startingPrice(array $hero, array $rooms): array
    {
        if (filled($hero['starting_price'] ?? null)) {
            return [
                'prefix' => 'Mulai',
                'amount' => (string) $hero['starting_price'],
                'unit' => '/pax',
            ];
        }

        $first = $rooms[0] ?? null;

        return [
            'prefix' => 'Mulai',
            'amount' => (string) ($first['price_label'] ?? '$16.750'),
            'unit' => (string) ($first['price_note'] ?? '/pax'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function lines(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, logo: string|null}>
     */
    public static function partnerAirlines(?Package $package = null, ?array $page = null): array
    {
        $page ??= self::content($package);
        $names = $page['partner_airlines'] ?? [];

        if ($names === []) {
            return HajiPlusProgram::partnerAirlines($package);
        }

        return array_values(array_map(
            fn (string $name) => ['name' => $name, 'logo' => Airline::logoFor($name)],
            $names,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $hotels
     * @return list<array<string, mixed>>
     */
    public static function resolveHotels(array $hotels): array
    {
        return array_map(function (array $hotel): array {
            if (($hotel['source'] ?? 'custom') !== 'master' || ! filled($hotel['master_name'] ?? null)) {
                return $hotel;
            }

            $location = (string) ($hotel['master_location'] ?? Hotel::LOCATION_MAKKAH);
            $name = (string) $hotel['master_name'];

            if (! filled($hotel['title'] ?? null)) {
                $hotel['title'] = $name;
            }

            if (! filled($hotel['city'] ?? null)) {
                $hotel['city'] = Hotel::LOCATIONS[$location] ?? ucfirst($location);
            }

            if (empty($hotel['image'])) {
                $logo = Hotel::logoFor($location, $name);
                if ($logo) {
                    $hotel['image'] = $logo;
                }
            }

            return $hotel;
        }, $hotels);
    }

    /**
     * @return array<string, mixed>
     */
    public static function blankHotel(): array
    {
        return self::hotelRow(
            source: 'custom',
            city: 'Makkah',
            title: 'Hotel baru',
            distance: '±100 m dari Masjidil Haram',
            featuresText: "Lokasi strategis\nAkses mudah",
            image: '',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function hotelRow(
        string $source,
        string $city,
        string $title,
        string $distance,
        string $featuresText,
        string $image = '',
        string $masterName = '',
        string $masterLocation = Hotel::LOCATION_MAKKAH,
        string $badge = 'Hotel pilihan',
    ): array {
        return [
            'source' => $source,
            'master_name' => $masterName,
            'master_location' => $masterLocation,
            'city' => $city,
            'title' => $title,
            'distance' => $distance,
            'features_text' => $featuresText,
            'image' => $image,
            'badge' => $badge,
        ];
    }

    /**
     * @param  list<string>  $defaults
     * @param  list<string>  $stored
     * @return list<string>
     */
    private static function mergePartnerAirlines(array $defaults, array $stored): array
    {
        $stored = array_values(array_filter(array_map(
            fn ($name) => trim((string) $name),
            $stored,
        )));

        return $stored !== [] ? $stored : $defaults;
    }

    /**
     * @param  list<array<string, mixed>>  $defaults
     * @param  list<array<string, mixed>>  $stored
     * @return list<array<string, mixed>>
     */
    private static function mergeHotels(array $defaults, array $stored): array
    {
        if ($stored === []) {
            return $defaults;
        }

        $keys = ['source', 'master_name', 'master_location', 'city', 'title', 'distance', 'features_text', 'image', 'badge'];
        $alwaysApply = ['source', 'master_name', 'master_location', 'image'];
        $fallback = $defaults[0] ?? self::blankHotel();
        $rows = [];

        foreach ($stored as $index => $row) {
            $base = $defaults[$index] ?? $fallback;
            $merged = $base;

            foreach ($keys as $key) {
                if (! array_key_exists($key, $row)) {
                    continue;
                }

                if (in_array($key, $alwaysApply, true) || ($row[$key] !== null && $row[$key] !== '')) {
                    $merged[$key] = $row[$key];
                }
            }

            $rows[] = $merged;
        }

        return $rows;
    }
    /**
     * @param  list<array<string, mixed>>  $defaults
     * @param  list<array<string, mixed>>  $stored
     * @param  list<string>  $keys
     * @return list<array<string, mixed>>
     */
    private static function mergeList(array $defaults, array $stored, array $keys): array
    {
        $rows = [];

        foreach ($defaults as $index => $default) {
            $row = $stored[$index] ?? [];
            $merged = $default;
            foreach ($keys as $key) {
                if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                    $merged[$key] = $row[$key];
                } elseif (array_key_exists($key, $row) && in_array($key, ['is_featured', 'show_quota'], true)) {
                    $merged[$key] = $row[$key];
                }
            }
            $rows[] = $merged;
        }

        return $rows;
    }
}
