<?php

namespace Database\Seeders;

use App\Models\Airline;
use Illuminate\Database\Seeder;

class AirlineSeeder extends Seeder
{
    public function run(): void
    {
        $airlines = [
            ['Garuda Indonesia', 10],
            ['Saudia', 20],
            ['Emirates', 30],
            ['Qatar Airways', 40],
            ['Turkish Airlines', 50],
            ['Etihad Airways', 60],
            ['Malaysia Airlines', 70],
            ['Lion Air', 80],
        ];

        foreach ($airlines as [$name, $sortOrder]) {
            Airline::query()->updateOrCreate(
                ['name' => $name],
                ['sort_order' => $sortOrder, 'is_active' => true],
            );
        }
    }
}
