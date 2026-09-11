<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Package;
use App\Models\PriceSyncChange;
use App\Models\PriceSyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_admin_can_view_sync_applied_changes_report(): void
    {
        $admin = User::factory()->admin()->create();
        $package = Package::query()->create([
            'title' => 'Mina GA CGK 12D JED',
            'slug' => 'mina-report-sync',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId('mina'),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-07',
            'duration_days' => 12,
            'price' => 38800000,
            'price_quad' => 38800000,
            'airline' => 'Garuda Indonesia',
            'room_type' => 'quad',
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'published',
            'images' => [],
            'source_key' => 'arminareka:7001',
        ]);

        $run = PriceSyncRun::query()->create([
            'trigger' => PriceSyncRun::TRIGGER_MANUAL,
            'status' => PriceSyncRun::STATUS_APPLIED,
            'started_at' => now(),
            'ended_at' => now(),
        ]);

        PriceSyncChange::query()->create([
            'price_sync_run_id' => $run->id,
            'package_id' => $package->id,
            'source_key' => 'arminareka:7001',
            'change_status' => PriceSyncChange::STATUS_CHANGED,
            'existing_snapshot' => [
                'title' => 'Mina GA CGK 12D JED',
                'departure_date' => '2026-12-07',
                'arrival_city' => 'jeddah',
            ],
            'incoming_snapshot' => [
                'title' => 'Mina GA CGK 12D JED',
                'departure_date' => '2026-12-14',
                'arrival_city' => 'madinah',
            ],
            'diff_fields' => ['departure_date', 'arrival_city'],
            'applied_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.price-sync.report'))
            ->assertOk()
            ->assertSee('Riwayat perubahan sync')
            ->assertSee('Mina GA CGK 12D JED')
            ->assertSee('Tanggal berangkat')
            ->assertSee('Tujuan penerbangan');
    }

    #[Test]
    public function test_admin_can_view_closing_report(): void
    {
        $admin = User::factory()->admin()->create();
        $package = Package::query()->create([
            'title' => 'Paket Closing Test',
            'slug' => 'paket-closing-test',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId('mina'),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-07',
            'duration_days' => 12,
            'price' => 38800000,
            'price_quad' => 38800000,
            'airline' => 'Garuda Indonesia',
            'room_type' => 'quad',
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'published',
            'images' => [],
        ]);

        Inquiry::query()->create([
            'name' => 'Jamaah Closing',
            'phone' => '081234567890',
            'package_id' => $package->id,
            'status' => Inquiry::STATUS_SOLD,
            'sold_pax' => 3,
            'sold_amount' => 120000000,
            'pax' => 3,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.closing'))
            ->assertOk()
            ->assertSee('Laporan closing paket')
            ->assertSee('Paket Closing Test')
            ->assertSee('3');
    }
}
