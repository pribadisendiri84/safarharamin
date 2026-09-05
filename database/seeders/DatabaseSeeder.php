<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@safarharamin.id'],
            [
                'name' => 'Admin SafarHaramin',
                'password' => Hash::make('admin123'),
                'role' => UserRole::Superadmin,
            ]
        );

        Setting::setValue('wa_number', '6281234567890');
        Setting::setValue('site_name', 'SafarHaramin');

        $this->call([
            CitySeeder::class,
            PackageKindSeeder::class,
            CatalogSeeder::class,
            PicSeeder::class,
            OperationsSeeder::class,
        ]);
    }
}
