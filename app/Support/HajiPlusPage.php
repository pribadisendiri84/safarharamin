<?php

namespace App\Support;

use App\Models\Airline;
use App\Models\HajiPageItinerary;
use App\Models\Hotel;
use App\Models\Setting;
use Illuminate\Support\Collection;

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
    public static function content(): array
    {
        $defaults = self::defaults();
        $stored = self::stored();

        $hotels = self::resolveHotels(self::mergeHotels($defaults['hotels'], $stored['hotels'] ?? []));
        $partnerAirlines = self::mergePartnerAirlines($defaults['partner_airlines'], $stored['partner_airlines'] ?? []);
        $airline = array_replace($defaults['airline'], $stored['airline'] ?? []);
        $airline['title'] = self::airlineTitle($partnerAirlines);

        return [
            'hero' => array_replace($defaults['hero'], $stored['hero'] ?? []),
            'rooms' => self::mergeList($defaults['rooms'], $stored['rooms'] ?? [], ['key', 'label', 'occupancy', 'price_label', 'price_note', 'image', 'is_featured']),
            'benefits' => self::mergeList($defaults['benefits'], $stored['benefits'] ?? [], ['title', 'description', 'icon']),
            'hotels' => $hotels,
            'partner_airlines' => $partnerAirlines,
            'airline' => $airline,
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
     * Field defaults untuk form keberangkatan operasional (salin, bukan relasi live).
     *
     * @return array<string, mixed>
     */
    public static function departureDefaults(): array
    {
        $page = self::content();
        $hero = $page['hero'];
        $hotels = collect($page['hotels']);
        $madinah = $hotels->first(fn (array $hotel) => ($hotel['master_location'] ?? '') === Hotel::LOCATION_MADINAH);
        $makkah = $hotels->first(fn (array $hotel) => ($hotel['master_location'] ?? '') === Hotel::LOCATION_MAKKAH);
        $season = trim((string) ($hero['season'] ?? ''));
        $title = trim((string) ($hero['title'] ?? HajiPlusProgram::HERO_TITLE));
        $programName = $season !== '' ? "{$title} — {$season}" : $title;

        return [
            'program_name' => $programName,
            'program_kind' => 'haji',
            'airline' => implode(' / ', $page['partner_airlines'] ?? []),
            'hotel_madinah' => (string) ($madinah['title'] ?? $madinah['master_name'] ?? ''),
            'hotel_makkah' => (string) ($makkah['title'] ?? $makkah['master_name'] ?? ''),
            'departure_date' => null,
            'flight_number' => null,
            'hotel_transit' => null,
            'hotel_maktab' => null,
            'notes' => null,
        ];
    }

    /**
     * Snapshot program haji saat masuk operasional — tidak berubah meski halaman/katalog diubah.
     *
     * @return array<string, mixed>
     */
    public static function operationalSnapshot(): array
    {
        $page = self::content();

        return [
            'captured_at' => now()->toIso8601String(),
            'hero' => $page['hero'],
            'rooms' => $page['rooms'],
            'hotels' => $page['hotels'],
            'partner_airlines' => $page['partner_airlines'],
            'airline' => $page['airline'],
            'benefits' => $page['benefits'],
            'official_itineraries' => self::itineraries()
                ->map(fn (HajiPageItinerary $item) => [
                    'departure_date' => $item->departure_date?->toDateString(),
                    'hijri_label' => $item->hijriLabel(),
                    'file_path' => $item->file_path,
                    'label' => $item->displayLabel(),
                ])
                ->values()
                ->all(),
            'departure_defaults' => self::departureDefaults(),
        ];
    }

    /**
     * @return Collection<int, HajiPageItinerary>
     */
    public static function itineraries(): Collection
    {
        return HajiPageItinerary::query()->ordered()->get();
    }

    /**
     * @return list<string>
     */
    public static function facilities(): array
    {
        return HajiPlusProgram::facilities();
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
    public static function defaults(): array
    {
        $rooms = HajiPlusProgram::roomOptions();

        return [
            'hero' => [
                'badge' => 'Haji Khusus',
                'season' => HajiPlusProgram::SEASON,
                'title' => HajiPlusProgram::HERO_TITLE,
                'subtitle' => HajiPlusProgram::HERO_SUBTITLE,
                'starting_price' => '',
                'show_quota' => '0',
                'image' => 'https://images.unsplash.com/photo-1564769625905-50e93615e769?w=1600&q=80',
            ],
            'rooms' => array_map(function (array $room) {
                return [
                    'key' => $room['key'],
                    'label' => $room['label'],
                    'occupancy' => str_replace('org/kamar', 'orang', $room['occupancy_label']),
                    'price_label' => $room['formatted_price_short'] ?: $room['price_usd'],
                    'price_note' => '/jamaah',
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
                    masterLocation: Hotel::LOCATION_MADINAH,
                    masterName: 'Madinah Pullman',
                    distance: '±100 m dari Masjid Nabawi',
                    featuresText: "Lokasi strategis\nAkses mudah ke masjid",
                ),
                self::hotelRow(
                    masterLocation: Hotel::LOCATION_MAKKAH,
                    masterName: 'Swissotel Makkah',
                    distance: '±100 m dari Masjidil Haram',
                    featuresText: "Lokasi strategis\nAkses mudah ke masjid",
                ),
            ],
            'partner_airlines' => ['Garuda Indonesia', 'Saudia'],
            'airline' => [
                'title' => self::airlineTitle(['Garuda Indonesia', 'Saudia']),
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
                'unit' => '/jamaah',
            ];
        }

        $first = $rooms[0] ?? null;

        return [
            'prefix' => 'Mulai',
            'amount' => (string) ($first['price_label'] ?? '$16.750'),
            'unit' => (string) ($first['price_note'] ?? '/jamaah'),
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
    public static function partnerAirlines(?array $page = null): array
    {
        $page ??= self::content();
        $names = $page['partner_airlines'] ?? [];

        if ($names === []) {
            return HajiPlusProgram::partnerAirlines();
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
            $location = (string) ($hotel['master_location'] ?? Hotel::LOCATION_MAKKAH);
            $name = trim((string) ($hotel['master_name'] ?? ''));

            if ($name === '') {
                return array_merge($hotel, [
                    'title' => 'Pilih hotel master',
                    'city' => Hotel::LOCATIONS[$location] ?? ucfirst($location),
                    'image' => $hotel['image'] ?? '',
                ]);
            }

            $stars = Hotel::starsFor($location, $name);
            $hotel['title'] = $name;
            $hotel['city'] = Hotel::LOCATIONS[$location] ?? ucfirst($location);
            $hotel['image'] = Hotel::logoFor($location, $name) ?: ($hotel['image'] ?? '');

            if ($stars && ! str_contains((string) ($hotel['features_text'] ?? ''), '★')) {
                $hotel['features_text'] = trim($stars."★\n".($hotel['features_text'] ?? ''));
            }

            return $hotel;
        }, $hotels);
    }

    /**
     * @param  list<string>  $partnerAirlines
     */
    public static function airlineTitle(array $partnerAirlines): string
    {
        $names = array_values(array_filter(array_map('trim', $partnerAirlines)));

        if ($names !== []) {
            return implode(' / ', $names);
        }

        return HajiPlusProgram::airlineLabel();
    }

    /**
     * @return array<string, mixed>
     */
    public static function blankHotel(): array
    {
        return self::hotelRow(
            masterLocation: Hotel::LOCATION_MADINAH,
            masterName: '',
            distance: '±100 m dari Masjid Nabawi',
            featuresText: "Lokasi strategis\nAkses mudah ke masjid",
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function hotelRow(
        string $masterLocation,
        string $masterName,
        string $distance,
        string $featuresText,
        string $badge = 'Hotel pilihan',
    ): array {
        return [
            'master_name' => $masterName,
            'master_location' => $masterLocation,
            'distance' => $distance,
            'features_text' => $featuresText,
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

        $keys = ['master_name', 'master_location', 'distance', 'features_text', 'badge'];
        $fallback = $defaults[0] ?? self::blankHotel();
        $rows = [];

        foreach ($stored as $index => $row) {
            $base = $defaults[$index] ?? $fallback;
            $merged = $base;

            foreach ($keys as $key) {
                if (! array_key_exists($key, $row)) {
                    continue;
                }

                if ($row[$key] !== null && $row[$key] !== '') {
                    $merged[$key] = $row[$key];
                }
            }

            if (empty($merged['master_name']) && filled($row['title'] ?? null) && ($row['source'] ?? '') === 'custom') {
                $merged['master_name'] = (string) $row['title'];
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
