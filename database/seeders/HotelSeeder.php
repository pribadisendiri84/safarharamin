<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = [
            [Hotel::LOCATION_MAKKAH, 'Swissotel Makkah', 10],
            [Hotel::LOCATION_MAKKAH, 'Makkah Clock Tower', 20],
            [Hotel::LOCATION_MAKKAH, 'Pullman Zamzam Makkah', 30],
            [Hotel::LOCATION_MAKKAH, 'Anjum Hotel Makkah', 40],
            [Hotel::LOCATION_MADINAH, 'Anwar Al Madinah Movenpick', 10],
            [Hotel::LOCATION_MADINAH, 'Madinah Oberoi', 20],
            [Hotel::LOCATION_MADINAH, 'Madinah Pullman', 30],
            [Hotel::LOCATION_MADINAH, 'Frontel Al Harithia', 40],
            [Hotel::LOCATION_TRANSIT, 'Transit Jeddah', 10],
            [Hotel::LOCATION_TRANSIT, 'Transit Dubai', 20],
            [Hotel::LOCATION_MAKTAB, 'Maktab Mina 5', 10],
            [Hotel::LOCATION_MAKTAB, 'Maktab Mina 12', 20],
            [Hotel::LOCATION_MAKTAB, 'Maktab Arafah', 30],
        ];

        foreach ($hotels as [$location, $name, $sortOrder]) {
            Hotel::query()->updateOrCreate(
                ['location' => $location, 'name' => $name],
                ['sort_order' => $sortOrder, 'is_active' => true],
            );
        }
    }
}
