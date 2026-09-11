<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDashboardCatalogAlertsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_dashboard_shows_catalog_alerts_for_catalog_managers(): void
    {
        Package::query()->create([
            'title' => 'Umroh Segera Berangkat',
            'slug' => 'umroh-segera',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->addDays(5)->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 20,
            'status' => 'published',
            'images' => ['/images/flyers/umroh1.png'],
        ]);

        Package::query()->create([
            'title' => 'Umroh Seat Menipis',
            'slug' => 'umroh-seat-tipis',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->addMonth()->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 3,
            'status' => 'published',
            'images' => ['/images/flyers/umroh1.png'],
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Mau kadaluarsa')
            ->assertSee('Seat menipis')
            ->assertSee('Umroh Segera Berangkat')
            ->assertSee('Umroh Seat Menipis');
    }

    #[Test]
    public function test_package_index_can_filter_by_catalog_alerts(): void
    {
        Package::query()->create([
            'title' => 'Umroh Segera Berangkat',
            'slug' => 'umroh-segera-filter',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->addDays(4)->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 20,
            'status' => 'published',
            'images' => ['/images/flyers/umroh1.png'],
        ]);

        Package::query()->create([
            'title' => 'Umroh Seat Menipis',
            'slug' => 'umroh-seat-tipis-filter',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->addMonth()->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 2,
            'status' => 'published',
            'images' => ['/images/flyers/umroh1.png'],
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.packages.index', ['expiring_soon' => 1]))
            ->assertOk()
            ->assertSee('Umroh Segera Berangkat')
            ->assertDontSee('Umroh Seat Menipis');

        $this->actingAs($admin)
            ->get(route('admin.packages.index', ['low_seats' => 1]))
            ->assertOk()
            ->assertSee('Umroh Seat Menipis')
            ->assertDontSee('Umroh Segera Berangkat');
    }
}
