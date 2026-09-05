<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = [
            [Hotel::LOCATION_MAKKAH, 'Swissotel Makkah', 5, 10],
            [Hotel::LOCATION_MAKKAH, 'Hilton', 5, 15],
            [Hotel::LOCATION_MAKKAH, 'Makkah Clock Tower', 5, 20],
            [Hotel::LOCATION_MAKKAH, 'Pullman Zamzam Makkah', 5, 30],
            [Hotel::LOCATION_MAKKAH, 'Anjum Hotel Makkah', 4, 40],
            [Hotel::LOCATION_MADINAH, 'Hilton', 5, 5],
            [Hotel::LOCATION_MADINAH, 'Anwar Al Madinah Movenpick', 5, 10],
            [Hotel::LOCATION_MADINAH, 'Madinah Oberoi', 5, 20],
            [Hotel::LOCATION_MADINAH, 'Madinah Pullman', 4, 30],
            [Hotel::LOCATION_MADINAH, 'Frontel Al Harithia', 4, 40],
            [Hotel::LOCATION_TRANSIT, 'Transit Jeddah', 4, 10],
            [Hotel::LOCATION_TRANSIT, 'Transit Dubai', 4, 20],
            [Hotel::LOCATION_MAKTAB, 'Maktab Mina 5', 3, 10],
            [Hotel::LOCATION_MAKTAB, 'Maktab Mina 12', 3, 20],
            [Hotel::LOCATION_MAKTAB, 'Maktab Arafah', 3, 30],
        ];

        $hasStars = Schema::hasColumn('hotels', 'stars');

        foreach ($hotels as [$location, $name, $stars, $sortOrder]) {
            $payload = ['sort_order' => $sortOrder, 'is_active' => true];
            if ($hasStars) {
                $payload['stars'] = $stars;
            }

            Hotel::query()->updateOrCreate(
                ['location' => $location, 'name' => $name],
                $payload,
            );
        }
    }
}
