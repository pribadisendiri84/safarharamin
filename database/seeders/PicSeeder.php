<?php

namespace Database\Seeders;

use App\Models\Pic;
use Illuminate\Database\Seeder;

class PicSeeder extends Seeder
{
    public function run(): void
    {
        $pics = [
            ['Yanti', 10],
            ['Budi Santoso', 20],
            ['Sari Dewi', 30],
        ];

        foreach ($pics as [$name, $sortOrder]) {
            Pic::query()->updateOrCreate(
                ['name' => $name],
                ['sort_order' => $sortOrder, 'is_active' => true],
            );
        }
    }
}
