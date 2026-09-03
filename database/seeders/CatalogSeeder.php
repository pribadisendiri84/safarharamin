<?php

namespace Database\Seeders;

use App\Models\GalleryItem;
use App\Models\Package;
use App\Models\Testimonial;
use App\Support\HomeDisplay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $photos = array_map(
            fn (int $n) => '/images/flyers/umroh'.$n.'.png',
            range(1, 8)
        );

        $defaults = [
            'Visa umroh/haji resmi',
            'Tiket pesawat PP',
            'Hotel sesuai paket',
            'Transportasi bus AC',
            'Muthawwif berbahasa Indonesia',
            'Manasik sebelum berangkat',
            'Perlengkapan jamaah',
            'Air zamzam 5L (ketentuan maskapai)',
        ];

        $exclusions = [
            'Paspor',
            'Vaksin',
            'Pengeluaran pribadi',
            'Tiket add-on PP',
        ];

        $rows = [
            ['Umroh Hemat 9 Hari Jakarta', 'umroh', 'jakarta', '2026-10-12', 9, 29500000, 32500000, 'Maysan Al Maqam', 'Odsyl', 4, 'Saudia', 'quad', 45, 12, true, true],
            ['Umroh Reguler 12 Hari Jakarta', 'umroh', 'jakarta', '2026-11-03', 12, 34500000, null, 'Azka Al Huda', 'Al Haram', 4, 'Garuda Indonesia', 'quad', 40, 18, true, false],
            ['Umroh Bintang 5 Ring 1', 'umroh', 'jakarta', '2026-12-08', 9, 45500000, 49900000, 'Makkah Clock Royal', 'Anwar Al Madinah', 5, 'Saudia', 'triple', 30, 6, true, true],
            ['Umroh Plus Turki 16 Hari', 'umroh_plus', 'jakarta', '2026-10-28', 16, 42900000, null, 'Maysan Al Maqam', 'Concorde', 4, 'Turkish Airlines', 'quad', 36, 9, true, true],
            ['Umroh Plus Dubai 14 Hari', 'umroh_plus', 'surabaya', '2026-11-18', 14, 39900000, 43500000, 'Ajyad Makkah', 'ODST', 4, 'Emirates', 'quad', 32, 14, true, false],
            ['Umroh Ramadhan 12 Hari', 'umroh_ramadhan', 'jakarta', '2027-03-02', 12, 48900000, null, 'Marwa Rotana', 'Al Haram', 5, 'Saudia', 'triple', 28, 4, true, true],
            ['Umroh Lailatul Qadar', 'umroh_ramadhan', 'medan', '2027-03-20', 10, 52500000, null, 'Hilton Suites', 'Anwar Al Madinah', 5, 'Saudia', 'double', 20, 3, true, true],
            ['Haji Plus 27 Hari', 'haji_plus', 'jakarta', '2027-05-20', 27, 275000000, null, 'Fairmont Makkah', 'Shaza Al Madina', 5, 'Garuda Indonesia', 'double', 20, 8, true, true],
            ['Haji Furoda 26 Hari', 'haji_furoda', 'jakarta', '2027-05-18', 26, 320000000, null, 'Raffles Makkah Palace', 'The Oberoi', 5, 'Saudia', 'double', 12, 5, true, false],
            ['Umroh 9 Hari Surabaya', 'umroh', 'surabaya', '2026-10-20', 9, 31200000, null, 'Ajyad Makkah', 'ODST', 4, 'Garuda Indonesia', 'quad', 40, 22, false, false],
            ['Umroh 9 Hari Medan', 'umroh', 'medan', '2026-11-09', 9, 32800000, 35000000, 'Maysan Al Maqam', 'Concorde', 4, 'Saudia', 'quad', 36, 16, false, false],
            ['Umroh Hemat Yogyakarta', 'umroh', 'yogyakarta', '2026-12-14', 9, 28900000, 31000000, 'Winner Inn Ajyad', 'ODST', 3, 'Lion Air', 'quad', 40, 25, true, false],
        ];

        $featuredSlot = 0;

        foreach ($rows as $i => $row) {
            [$title, $type, $city, $date, $days, $price, $original, $makkah, $madinah, $stars, $airline, $room, $total, $left, $featured, $hot] = $row;
            $rooms = $this->roomPrices($price);
            $homeSort = null;
            if ($featured) {
                $featuredSlot++;
                $homeSort = $featuredSlot <= HomeDisplay::packageLimit() ? $featuredSlot : 0;
            }

            Package::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'type' => $type,
                    'departure_city' => $city,
                    'departure_date' => $date,
                    'duration_days' => $days,
                    'price' => $price,
                    'price_quad' => $rooms['quad'],
                    'price_triple' => $rooms['triple'],
                    'price_double' => $rooms['double'],
                    'original_price' => $original,
                    'price_note' => 'Harga dapat berubah sesuai kebijakan perusahaan.',
                    'hotel_makkah' => $makkah,
                    'hotel_madinah' => $madinah,
                    'hotel_stars' => $stars,
                    'airline' => $airline,
                    'room_type' => $room,
                    'seats_total' => $total,
                    'seats_left' => $left,
                    'facilities' => $defaults,
                    'exclusions' => $exclusions,
                    'description' => 'Paket '.$title.' dengan hotel bintang '.$stars.', maskapai '.$airline.', dan pendampingan muthawwif berbahasa Indonesia.',
                    'itinerary' => "Hari 1: Berkumpul embarkasi & terbang ke Jeddah/Madinah.\nHari 2-3: Ibadah di Madinah.\nHari 4: Menuju Makkah, umroh.\nHari 5-7: Ibadah di Masjidil Haram.\nHari terakhir: Kepulangan sesuai jadwal maskapai.",
                    'images' => [$photos[$i % count($photos)]],
                    'is_featured' => $featured,
                    'home_sort' => $homeSort,
                    'is_hot' => $hot,
                    'status' => 'published',
                ]
            );
        }

        $quotes = [
            ['Ibu Aisyah', 'Jakarta', 'Umroh Hemat 9 Hari Jakarta', 'Hotel dekat, pembimbing sabar, anak-anak tidak ketinggalan rombongan.'],
            ['Pak Hasan', 'Surabaya', 'Umroh Plus Turki 16 Hari', 'Umroh khusyuk, city tour Istanbul rapi. Seat sesuai yang dijanjikan.'],
            ['Ny. Fatimah', 'Medan', 'Umroh Ramadhan 12 Hari', 'Tarawih di Haram tidak terlupakan. Tim selalu siap membantu lansia.'],
            ['H. Rahman', 'Jakarta', 'Haji Plus 27 Hari', 'Arafah dan Mina tertata. Kami tidak bingung mencari tenda.'],
        ];

        foreach ($quotes as $i => $row) {
            Testimonial::query()->updateOrCreate(
                ['name' => $row[0], 'package_title' => $row[2]],
                [
                    'city' => $row[1],
                    'quote' => $row[3],
                    'is_published' => true,
                    'sort_order' => $i,
                ]
            );
        }

        $gallery = [
            ['Manasik Jakarta', $photos[0], 'Persiapan sebelum berangkat', 'umroh', 'Manasik'],
            ['Keberangkatan Soekarno-Hatta', $photos[3], 'Rombongan embarkasi Jakarta', 'umroh', 'Bandara'],
            ['Masjid Nabawi', $photos[1], 'Jamaah di Madinah', 'umroh', 'Madinah'],
            ['Masjidil Haram', $photos[0], 'Thawaf bersama muthawwif', 'umroh', 'Makkah'],
            ['Rombongan Madinah', $photos[2], 'Hotel dekat Gate 328', 'umroh', 'Madinah'],
            ['Kepulangan jamaah', $photos[3], 'Tiba kembali di tanah air', 'umroh', 'Bandara'],
            ['Manasik haji', $photos[0], 'Briefing jamaah haji', 'haji', 'Manasik'],
            ['Keberangkatan haji', $photos[3], 'Embarkasi rombongan haji', 'haji', 'Bandara'],
            ['Ibadah di Mina', $photos[1], 'Dokumentasi haji plus', 'haji', 'Ibadah'],
        ];

        foreach ($gallery as $i => $row) {
            GalleryItem::query()->updateOrCreate(
                ['title' => $row[0]],
                [
                    'image' => $row[1],
                    'caption' => $row[2],
                    'category' => $row[3],
                    'group_name' => $row[4],
                    'sort_order' => $i,
                    'show_on_home' => $i < 9,
                    'home_sort' => $i < 9 ? $i + 1 : null,
                ]
            );
        }
    }

    /**
     * @return array{quad: int, triple: int, double: int}
     */
    private function roomPrices(int $quad): array
    {
        return [
            'quad' => $quad,
            'triple' => $quad + 1100000,
            'double' => $quad + 3400000,
        ];
    }
}
