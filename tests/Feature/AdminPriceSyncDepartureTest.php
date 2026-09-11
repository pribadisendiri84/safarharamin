<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PriceSyncChange;
use App\Models\PriceSyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPriceSyncDepartureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_admin_can_apply_new_departure_by_cloning_existing_package(): void
    {
        config(['arminareka.jadwal.request_delay_ms' => 0]);

        Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-base',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId('muzdalifah'),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-11-30',
            'duration_days' => 12,
            'price' => 38800000,
            'price_quad' => 38800000,
            'price_triple' => 40300000,
            'price_double' => 43400000,
            'airline' => 'Lion Air',
            'room_type' => 'quad',
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'published',
            'images' => ['/images/flyers/umroh1.png'],
            'facilities' => ['Tiket PP'],
            'hotel_makkah' => 'Hilton',
        ]);

        $html = <<<'HTML'
<table><tbody>
<tr data-key="999"><td>1</td><td>Muzdalifah JT CGK 12D JED (07/12/2026)</td><td>7 Dec 2026</td><td>39.000.000</td><td>40.300.000</td><td>43.400.000</td><td>45</td><td>45</td></tr>
</tbody></table>
HTML;

        Http::fake(['*' => Http::response($html, 200)]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.price-sync.store'));

        $run = PriceSyncRun::query()->firstOrFail();
        $change = $run->changes()->where('change_status', PriceSyncChange::STATUS_NEW_DEPARTURE)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.price-sync.apply', $run), ['change_ids' => [$change->id]])
            ->assertRedirect(route('admin.price-sync.show', $run));

        $this->assertDatabaseHas('packages', [
            'title' => 'Muzdalifah JT CGK 12D JED',
            'departure_date' => '2026-12-07 00:00:00',
            'source_key' => 'arminareka:999',
            'price_quad' => 39000000,
            'status' => 'draft',
            'hotel_makkah' => 'Hilton',
        ]);

        $this->assertSame(2, Package::query()->where('title', 'Muzdalifah JT CGK 12D JED')->count());
    }
}
