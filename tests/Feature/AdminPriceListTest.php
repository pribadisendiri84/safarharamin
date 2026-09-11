<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Support\PriceSyncSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPriceListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_admin_can_view_grouped_price_list(): void
    {
        Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED (07/12/2026)',
            'slug' => 'price-list-a',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId('muzdalifah'),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-07',
            'duration_days' => 12,
            'price' => 38800000,
            'price_quad' => 38800000,
            'airline' => 'Lion Air',
            'room_type' => 'quad',
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'published',
            'images' => [],
        ]);

        Package::query()->create([
            'title' => 'Mina GA CGK 9D JED (30/11/2026)',
            'slug' => 'price-list-b',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId('mina'),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-11-30',
            'duration_days' => 9,
            'price' => 38800000,
            'price_quad' => 38800000,
            'airline' => 'Garuda Indonesia',
            'room_type' => 'quad',
            'seats_total' => 90,
            'seats_left' => 90,
            'status' => 'draft',
            'images' => [],
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.price-list.index'))
            ->assertOk()
            ->assertSee('Price List')
            ->assertSee('Umroh Reguler')
            ->assertSee('Lion Air')
            ->assertSee('Garuda Indonesia')
            ->assertSee('Muzdalifah JT CGK 12D JED (07/12/2026)');
    }

    #[Test]
    public function test_admin_can_update_sync_schedule(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.price-sync.schedule.update'), [
                'enabled' => '1',
                'frequency' => 'daily',
                'time' => '03:30',
                'days' => [1, 3, 5],
                'update_mode' => 'manual_approval',
                'timezone' => 'Asia/Jakarta',
            ])
            ->assertRedirect(route('admin.price-sync.schedule.edit'));

        $config = PriceSyncSchedule::config();
        $this->assertTrue($config['enabled']);
        $this->assertSame('03:30', $config['time']);
        $this->assertSame('manual_approval', $config['update_mode']);
    }
}
