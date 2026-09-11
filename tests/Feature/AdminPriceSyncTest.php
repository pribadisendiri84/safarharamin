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

class AdminPriceSyncTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_admin_can_run_sync_preview_and_apply_selected_changes(): void
    {
        config([
            'arminareka.jadwal.request_delay_ms' => 0,
            'arminareka.jadwal.page_size' => 15,
            'arminareka.jadwal.max_pages' => 2,
        ]);

        Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-sync-feature',
            'source_key' => 'arminareka:5928',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId('muzdalifah'),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-07',
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
            'images' => [],
        ]);

        $html = <<<'HTML'
<table><tbody>
<tr data-key="5928"><td>1</td><td>Muzdalifah JT CGK 12D JED (07/12/2026)</td><td>7 Dec 2026</td><td>39.000.000</td><td>40.300.000</td><td>43.400.000</td><td>45</td><td>40</td></tr>
</tbody></table>
HTML;

        Http::fake(['*' => Http::response($html, 200)]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.price-sync.store'))
            ->assertRedirect();

        $run = PriceSyncRun::query()->first();
        $this->assertNotNull($run);
        $this->assertSame(1, $run->total_found);

        $change = $run->changes()->where('change_status', PriceSyncChange::STATUS_CHANGED)->first();
        $this->assertNotNull($change);

        $this->actingAs($admin)
            ->get(route('admin.price-sync.show', $run))
            ->assertOk()
            ->assertSee('Preview Changes')
            ->assertSee('Update harga');

        $this->actingAs($admin)
            ->post(route('admin.price-sync.apply', $run), [
                'change_ids' => [$change->id],
            ])
            ->assertRedirect(route('admin.price-sync.show', $run));

        $package = Package::query()->where('source_key', 'arminareka:5928')->firstOrFail();
        $this->assertSame(39000000, (int) $package->price_quad);
        $this->assertSame(40, (int) $package->seats_left);
        $this->assertSame('published', $package->status);
        $this->assertNotNull($change->fresh()->applied_at);
    }

    #[Test]
    public function test_admin_can_apply_source_key_when_package_matches_by_title_and_date(): void
    {
        config([
            'arminareka.jadwal.request_delay_ms' => 0,
            'arminareka.jadwal.page_size' => 15,
            'arminareka.jadwal.max_pages' => 2,
        ]);

        $package = Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-link-source-key',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId('muzdalifah'),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-07',
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
            'images' => [],
        ]);

        $html = <<<'HTML'
<table><tbody>
<tr data-key="5928"><td>1</td><td>Muzdalifah JT CGK 12D JED (07/12/2026)</td><td>7 Dec 2026</td><td>38.800.000</td><td>40.300.000</td><td>43.400.000</td><td>45</td><td>45</td></tr>
</tbody></table>
HTML;

        Http::fake(['*' => Http::response($html, 200)]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.price-sync.store'))
            ->assertRedirect();

        $run = PriceSyncRun::query()->firstOrFail();
        $change = $run->changes()->where('change_status', PriceSyncChange::STATUS_CHANGED)->firstOrFail();
        $this->assertContains('source_key', $change->diff_fields);

        $this->actingAs($admin)
            ->post(route('admin.price-sync.apply', $run), [
                'change_ids' => [$change->id],
            ])
            ->assertRedirect(route('admin.price-sync.show', $run));

        $this->assertSame('arminareka:5928', $package->fresh()->source_key);
    }

    #[Test]
    public function test_sync_history_page_is_accessible(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.price-sync.index'))
            ->assertOk()
            ->assertSee('Sync Harga Arminareka');
    }
}
