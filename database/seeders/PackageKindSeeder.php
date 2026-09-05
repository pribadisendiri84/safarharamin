<?php

namespace Database\Seeders;

use App\Models\PackageKind;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PackageKindSeeder extends Seeder
{
    public function run(): void
    {
        $kinds = [
            ['Arafah', 10],
            ['Mina', 20],
            ['Muzdalifah', 30],
        ];

        foreach ($kinds as [$name, $sortOrder]) {
            PackageKind::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ],
            );
        }
    }
}
