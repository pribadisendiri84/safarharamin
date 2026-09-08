<?php

namespace App\Support;

use App\Models\Airline;
use App\Models\Package;

class HajiPlusProgram
{
    public const TITLE = 'Program Haji Plus Arminareka';

    public const HERO_TITLE = 'Haji Khusus Arminareka';

    public const HERO_SUBTITLE = 'Program Haji Plus dengan fasilitas terbaik, nyaman, dan didampingi pembimbing berpengalaman.';

    public const SEASON = '1447H/2026M';

    public const PAGE_INTRO = 'Satu program Haji Khusus Arminareka Perdana dengan pilihan kamar Quad, Triple, Double, dan Double Plus. Tim kami siap menjelaskan legalitas, biaya, fasilitas, dan tahapan pendaftaran sebelum Bapak/Ibu memutuskan.';

    public const TAGLINE = 'Wujudkan ibadah haji yang mabrur bersama kami.';

    public const CLOSING_LINE = 'Arminareka Perdana, antara Anda dan Baitullah.';

    public static function primary(): ?Package
    {
        return Package::query()
            ->published()
            ->whereIn('type', Package::HAJI_TYPES)
            ->orderByDesc('is_featured')
            ->orderBy('home_sort')
            ->orderBy('price')
            ->first();
    }

    /**
     * @return array{porsi: string, dp: string, dp_idr_note: string, summary: string}
     */
    public static function initialDeposit(): array
    {
        return [
            'porsi' => 'USD 4.000',
            'dp' => 'USD 500',
            'dp_idr_note' => '~Rp 5 juta',
            'summary' => 'Mulai dengan setoran awal porsi USD 4.000 + DP Haji Khusus USD 500',
        ];
    }

    /**
     * Referensi harga & akomodasi dari flyer resmi program.
     *
     * @return list<array{key: string, label: string, room_label: string, occupancy_label: string, price_usd: string, hotel_note: string, transit_note: string}>
     */
    public static function flyerRooms(): array
    {
        return [
            [
                'key' => 'quad',
                'label' => 'Quad',
                'room_label' => 'Kamar berempat',
                'occupancy_label' => '4 org/kamar',
                'price_usd' => '$16.750',
                'hotel_note' => 'Hotel Madinah – Makkah: sekamar berempat',
                'transit_note' => 'Hotel transit: sekamar berempat',
            ],
            [
                'key' => 'triple',
                'label' => 'Triple',
                'room_label' => 'Kamar bertiga',
                'occupancy_label' => '3 org/kamar',
                'price_usd' => '$17.750',
                'hotel_note' => 'Hotel Madinah – Makkah: sekamar bertiga',
                'transit_note' => 'Hotel transit: sekamar berempat',
            ],
            [
                'key' => 'double',
                'label' => 'Double',
                'room_label' => 'Kamar berdua',
                'occupancy_label' => '2 org/kamar',
                'price_usd' => '$18.500',
                'hotel_note' => 'Hotel Madinah – Makkah: sekamar berdua',
                'transit_note' => 'Hotel transit: sekamar berempat',
            ],
            [
                'key' => 'double_plus',
                'label' => 'Double Plus',
                'room_label' => 'Kamar berdua plus',
                'occupancy_label' => '2 org/kamar',
                'price_usd' => '$19.800',
                'hotel_note' => 'Hotel Madinah – Makkah: sekamar berdua plus',
                'transit_note' => 'Hotel transit: sekamar berdua',
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, room_label: string, occupancy: int, occupancy_label: string, full_label: string, price: int|null, formatted_price: string|null, formatted_price_short: string|null, price_usd: string, display_price: string, display_price_note: string, hotel_note: string, transit_note: string}>
     */
    public static function roomOptions(?Package $package = null): array
    {
        $flyer = collect(self::flyerRooms())->keyBy('key');
        $priced = $package
            ? collect($package->roomPriceList())->keyBy('key')
            : collect();

        $rows = [];

        foreach ($flyer as $key => $flyerRow) {
            $occupancy = Package::ROOM_OCCUPANCY[$key] ?? 0;
            $price = $priced->get($key)['price'] ?? null;
            $formattedPrice = $price && $package ? $package->formattedMoney($price) : null;
            $formattedPriceShort = $price && $package ? $package->formattedMoneyShort($price) : null;

            if ($formattedPriceShort) {
                $displayPrice = $formattedPriceShort;
                $displayPriceNote = '/jamaah';
            } else {
                $displayPrice = $flyerRow['price_usd'];
                $displayPriceNote = '/jamaah';
            }

            $rows[] = [
                'key' => $key,
                'label' => $flyerRow['label'],
                'room_label' => $flyerRow['room_label'],
                'occupancy' => $occupancy,
                'occupancy_label' => $flyerRow['occupancy_label'],
                'full_label' => $flyerRow['room_label'].' ('.$flyerRow['occupancy_label'].')',
                'price' => $price,
                'formatted_price' => $formattedPrice,
                'formatted_price_short' => $formattedPriceShort,
                'price_usd' => $flyerRow['price_usd'],
                'display_price' => $displayPrice,
                'display_price_note' => $displayPriceNote,
                'hotel_note' => $flyerRow['hotel_note'],
                'transit_note' => $flyerRow['transit_note'],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{key: string, label: string, room_label: string, capacity: string, hotel_note: string, transit_note: string, price_note: string}>
     */
    public static function roomComparison(): array
    {
        return array_map(function (array $row) {
            return [
                'key' => $row['key'],
                'label' => $row['label'],
                'room_label' => $row['room_label'],
                'capacity' => $row['occupancy_label'],
                'hotel_note' => $row['hotel_note'],
                'transit_note' => $row['transit_note'],
                'price_note' => 'Referensi flyer '.$row['price_usd'].' / jamaah',
            ];
        }, self::flyerRooms());
    }

    /**
     * @return list<array{title: string, description: string, icon: string}>
     */
    public static function highlights(): array
    {
        return [
            [
                'title' => 'Amanah & terpercaya',
                'description' => 'Penyelenggara berizin resmi dengan pendampingan jamaah yang transparan.',
                'icon' => 'bi-shield-check',
            ],
            [
                'title' => 'Pembimbing berpengalaman',
                'description' => 'Tim muthawwif dan koordinator lapangan yang memahami kebutuhan jamaah Indonesia.',
                'icon' => 'bi-person-badge',
            ],
            [
                'title' => 'Layanan 24 jam di Tanah Suci',
                'description' => 'Dukungan selama perjalanan ibadah berlangsung.',
                'icon' => 'bi-headset',
            ],
            [
                'title' => 'Penerbangan langsung',
                'description' => 'Program dirancang dengan penerbangan langsung sesuai jadwal keberangkatan.',
                'icon' => 'bi-airplane',
            ],
        ];
    }

    /**
     * @return list<array{title: string, description: string, icon: string}>
     */
    public static function consultationTopics(): array
    {
        return [
            ['title' => 'Legalitas', 'description' => 'Izin resmi Haji Plus Arminareka dan legalitas perusahaan.', 'icon' => 'bi-patch-check'],
            ['title' => 'Proses pendaftaran', 'description' => 'Tahapan daftar dari formulir hingga konfirmasi porsi.', 'icon' => 'bi-clipboard-check'],
            ['title' => 'Biaya & pembayaran', 'description' => 'Setoran awal, DP, dan skema pelunasan program.', 'icon' => 'bi-cash-coin'],
            ['title' => 'Estimasi masa tunggu', 'description' => 'Perkiraan proses penerbitan porsi haji.', 'icon' => 'bi-hourglass-split'],
            ['title' => 'Fasilitas', 'description' => 'Hotel, penerbangan, dan pembimbing ibadah.', 'icon' => 'bi-building'],
            ['title' => 'Durasi program', 'description' => 'Lama perjalanan dan rangkaian ibadah haji.', 'icon' => 'bi-calendar3'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function facilities(): array
    {
        return [
            'Visa haji resmi',
            'Tiket pesawat pulang-pergi (penerbangan langsung)',
            'Hotel Madinah, Makkah, transit, dan maktab sesuai program',
            'Transportasi bus AC',
            'Muthawwif / pembimbing ibadah berbahasa Indonesia',
            'Manasik sebelum berangkat',
            'Perlengkapan jamaah',
            'Layanan pendampingan selama di Tanah Suci',
        ];
    }

    /**
     * @return list<array{step: string, title: string, description: string}>
     */
    public static function registrationSteps(): array
    {
        return [
            [
                'step' => '1',
                'title' => 'Isi formulir pendaftaran',
                'description' => 'Mengisi formulir pendaftaran calon jamaah Haji Plus.',
            ],
            [
                'step' => '2',
                'title' => 'Bayar setoran awal',
                'description' => 'Membayarkan DP Haji Rp 5 juta dan porsi Haji Plus USD 4.000 (sesuai ketentuan program).',
            ],
            [
                'step' => '3',
                'title' => 'Setor ke rekening resmi',
                'description' => 'Menyetorkan setoran awal ke rekening Arminareka Perdana.',
            ],
            [
                'step' => '4',
                'title' => 'Kirim dokumen kelengkapan',
                'description' => 'Mengirim salinan KTP, Kartu Keluarga, Buku Nikah atau Akta Lahir, dan pasfoto wajah 80%.',
            ],
            [
                'step' => '5',
                'title' => 'Lampirkan bukti setor',
                'description' => 'Sertakan bukti setor pembayaran DP dan porsi haji untuk diproses lebih lanjut oleh tim kami.',
            ],
        ];
    }

    public static function porsiProcessingEstimate(): string
    {
        return 'Estimasi proses penerbitan porsi haji berlangsung sekitar 2–4 minggu.';
    }

    /**
     * @return list<string>
     */
    public static function requirements(): array
    {
        return [
            'Warga Negara Indonesia beragama Islam.',
            'Memenuhi persyaratan usia dan kesehatan sesuai ketentuan Kemenag.',
            'Siap mengikuti manasik dan briefing sebelum keberangkatan.',
            'Bersedia melengkapi dokumen porsi haji sesuai instruksi tim.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function documents(): array
    {
        return [
            'KTP',
            'Kartu Keluarga',
            'Buku Nikah atau Akta Lahir',
            'Pasfoto wajah 80%',
            'Paspor (jika sudah tersedia)',
            'Bukti setor DP dan porsi haji',
        ];
    }

    /**
     * @return list<string>
     */
    public static function policies(): array
    {
        return [
            'Harga paket mengacu pada flyer program musim berjalan dan dapat disesuaikan dengan kurs/ketentuan terbaru.',
            'Setoran awal porsi USD 4.000 dan DP Haji Khusus USD 500 (~Rp 5 juta) mengikuti kebijakan pada saat pendaftaran.',
            'Pembatalan, perubahan jadwal, dan pelunasan mengikuti ketentuan resmi yang berlaku.',
            'Informasi hotel, maskapai, dan fasilitas dapat disesuaikan setaraf jika diperlukan untuk kelancaran program.',
            'Jamaah wajib mengikuti arahan muthawwif dan koordinator rombongan selama perjalanan.',
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public static function faq(): array
    {
        return [
            [
                'question' => 'Apakah Quad, Triple, Double, dan Double Plus adalah paket haji yang berbeda?',
                'answer' => 'Tidak. Keempat opsi itu adalah pilihan tipe kamar dalam satu program Haji Plus. Perbedaannya ada pada kapasitas kamar, akomodasi transit, dan harga per jamaah.',
            ],
            [
                'question' => 'Berapa setoran awal untuk mendaftar?',
                'answer' => 'Sesuai flyer program: setoran awal porsi USD 4.000 ditambah DP Haji Khusus USD 500 (~Rp 5 juta). Tim kami akan menjelaskan detail rekening dan tahap berikutnya.',
            ],
            [
                'question' => 'Berapa lama proses penerbitan porsi haji?',
                'answer' => self::porsiProcessingEstimate().' Waktu aktual dapat berbeda tergantung kelengkapan dokumen dan antrian musim berjalan.',
            ],
            [
                'question' => 'Apa beda Double dan Double Plus?',
                'answer' => 'Keduanya sekamar berdua di hotel Madinah–Makkah. Double Plus memberikan akomodasi lebih premium, termasuk hotel transit sekamar berdua (bukan berempat).',
            ],
            [
                'question' => 'Apa saja yang akan dijelaskan saat konsultasi?',
                'answer' => 'Tim kami menjelaskan legalitas, proses pendaftaran, biaya & pembayaran, estimasi masa tunggu, fasilitas (hotel, penerbangan, pembimbing ibadah), serta durasi program.',
            ],
        ];
    }

    public static function consultationMessage(): string
    {
        $site = SiteProfile::current()->name;

        return "Halo {$site}, saya ingin konsultasi program Haji Plus Arminareka. Mohon info tipe kamar, biaya setoran awal, dan tahapan pendaftaran.";
    }

    public static function roomConsultationMessage(string $roomLabel): string
    {
        $site = SiteProfile::current()->name;

        return "Halo {$site}, saya tertarik program Haji Plus — tipe kamar {$roomLabel}. Mohon info harga dan ketersediaan.";
    }

    public static function whatsAppUrl(): string
    {
        return route('go.haji.whatsapp', ['intent' => 'consult']);
    }

    public static function roomConsultationUrl(string $roomLabel): string
    {
        return route('go.haji.whatsapp', [
            'intent' => 'room',
            'room' => $roomLabel,
        ]);
    }

    public static function registerUrl(?string $room = null): string
    {
        return route('register', array_filter([
            'program' => 'haji',
            'room' => $room,
        ]));
    }

    /**
     * @return array{prefix: string, amount: string, unit: string}
     */
    public static function startingPrice(?Package $package = null): array
    {
        $rooms = self::roomOptions($package);
        $cheapest = collect($rooms)
            ->sortBy(fn (array $row) => $row['price'] ?? PHP_INT_MAX)
            ->first() ?? $rooms[0];

        if (! empty($cheapest['formatted_price_short'])) {
            return [
                'prefix' => 'Mulai',
                'amount' => $cheapest['formatted_price_short'],
                'unit' => '/jamaah',
            ];
        }

        return [
            'prefix' => 'Mulai',
            'amount' => $cheapest['price_usd'] ?? '$16.750',
            'unit' => '/jamaah',
        ];
    }

    public static function showLimitedQuota(?Package $package = null): bool
    {
        $package ??= self::primary();

        if (! $package) {
            return false;
        }

        return $package->is_hot
            || ($package->showsSeats() && $package->seats_left <= 10);
    }

    public static function featuredRoomKey(): string
    {
        return 'quad';
    }

    /**
     * @return list<array{name: string, logo: string|null}>
     */
    public static function partnerAirlines(): array
    {
        $names = ['Garuda Indonesia', 'Saudia'];

        return array_map(
            fn (string $name) => ['name' => $name, 'logo' => Airline::logoFor($name)],
            $names,
        );
    }

    public static function airlineLabel(): string
    {
        return 'Garuda Indonesia · Saudia';
    }

    /**
     * Referensi harga IDR per tipe kamar (nominal penuh, bukan teks tampilan).
     *
     * @return array{quad: int, triple: int, double: int, double_plus: int}
     */
    public static function defaultRoomPricesIdr(int $quad = 275_000_000): array
    {
        return [
            'quad' => $quad,
            'triple' => $quad + 1_100_000,
            'double' => $quad + 3_400_000,
            'double_plus' => $quad + 5_500_000,
        ];
    }

    /**
     * @return list<array{title: string, description: string, icon: string}>
     */
    public static function benefits(): array
    {
        return [
            [
                'title' => 'Hotel strategis',
                'description' => 'Dekat Masjidil Haram & Nabawi.',
                'icon' => 'bi-building',
            ],
            [
                'title' => 'Maskapai terbaik',
                'description' => 'Garuda Indonesia · Saudia.',
                'icon' => 'bi-airplane',
            ],
            [
                'title' => 'Pembimbing profesional',
                'description' => 'Mendampingi dari tanah air hingga pulang.',
                'icon' => 'bi-person-badge',
            ],
            [
                'title' => 'Visa resmi',
                'description' => 'Proses aman & terpercaya.',
                'icon' => 'bi-patch-check',
            ],
            [
                'title' => 'Pelayanan prima',
                'description' => 'Tim berpengalaman dan responsif.',
                'icon' => 'bi-headset',
            ],
        ];
    }

    /**
     * @return list<array{city: string, badge: string, title: string, distance: string, features: list<string>, image: string}>
     */
    public static function hotelShowcase(?Package $package = null): array
    {
        $package ??= self::primary();

        return [
            [
                'city' => 'Madinah',
                'badge' => 'Hotel pilihan',
                'title' => $package?->hotel_madinah ?: 'Hotel dekat Masjid Nabawi',
                'distance' => '±100 m dari Masjid Nabawi',
                'features' => [
                    'Lokasi strategis',
                    'Bus antar-jemput',
                    'Makan sesuai program',
                ],
                'image' => 'https://images.unsplash.com/photo-1542816417-0983c9fa1534?w=900&q=80',
            ],
            [
                'city' => 'Makkah',
                'badge' => 'Hotel pilihan',
                'title' => $package?->hotel_makkah ?: 'Hotel dekat Masjidil Haram',
                'distance' => '±100 m dari Masjidil Haram',
                'features' => [
                    'Dekat Haram',
                    'View kota suci',
                    'Fasilitas lengkap',
                ],
                'image' => 'https://images.unsplash.com/photo-1564769625905-50e93615e769?w=900&q=80',
            ],
        ];
    }

    /**
     * @return list<array{step: string, title: string, description: string}>
     */
    public static function registrationFlow(): array
    {
        return [
            [
                'step' => '1',
                'title' => 'Konsultasi',
                'description' => 'Diskusi kebutuhan & pilih tipe kamar terbaik.',
            ],
            [
                'step' => '2',
                'title' => 'Pendaftaran',
                'description' => 'Lengkapi data dan persyaratan awal.',
            ],
            [
                'step' => '3',
                'title' => 'Pembayaran',
                'description' => 'Lakukan setoran awal sesuai ketentuan.',
            ],
            [
                'step' => '4',
                'title' => 'Pelunasan & berangkat',
                'description' => 'Proses akhir hingga jadwal keberangkatan.',
            ],
        ];
    }

    public static function roomImage(string $key): string
    {
        return match ($key) {
            'quad' => 'https://images.unsplash.com/photo-1618773928123-c223d0f8b969?w=700&q=80',
            'triple' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=700&q=80',
            'double' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=700&q=80',
            'double_plus' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=700&q=80',
            default => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=700&q=80',
        };
    }
}
