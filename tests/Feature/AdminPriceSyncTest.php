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
            ->assertSee('Periode (DB)')
            ->assertSee('Bedanya')
            ->assertDontSee('Harga web')
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
        $this->assertSame($package->id, $change->fresh()->package_id);
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
    public function test_admin_can_apply_replacement_source_key_when_rescheduled_on_arminareka(): void
    {
        config([
            'arminareka.jadwal.request_delay_ms' => 0,
            'arminareka.jadwal.page_size' => 15,
            'arminareka.jadwal.max_pages' => 2,
        ]);

        $package = Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-rescheduled-id',
            'source_key' => 'arminareka:100',
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
<tr data-key="999"><td>1</td><td>Muzdalifah JT CGK 12D JED (07/12/2026)</td><td>7 Dec 2026</td><td>38.800.000</td><td>40.300.000</td><td>43.400.000</td><td>45</td><td>45</td></tr>
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
        $this->assertSame('arminareka:999', $change->source_key);

        $this->actingAs($admin)
            ->post(route('admin.price-sync.apply', $run), [
                'change_ids' => [$change->id],
            ])
            ->assertRedirect(route('admin.price-sync.show', $run));

        $this->assertSame('arminareka:999', $package->fresh()->source_key);
        $this->assertSame(0, $run->fresh()->changes()->where('change_status', PriceSyncChange::STATUS_REMOVED)->count());
    }

    #[Test]
    public function test_apply_changed_updates_title_when_source_key_matches(): void
    {
        $kindId = $this->packageKindId('muzdalifah');

        $package = Package::query()->create([
            'title' => 'MUZDALIFAH JT PDG 12Hr JED',
            'slug' => 'muzdalifah-title-apply',
            'source_key' => 'arminareka:8001',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
            'departure_city' => 'padang',
            'arrival_city' => 'jeddah',
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

        $run = PriceSyncRun::query()->create([
            'trigger' => PriceSyncRun::TRIGGER_MANUAL,
            'status' => PriceSyncRun::STATUS_WAITING,
            'started_at' => now(),
        ]);

        $change = $run->changes()->create([
            'package_id' => $package->id,
            'change_status' => PriceSyncChange::STATUS_CHANGED,
            'source_key' => 'arminareka:8001',
            'external_id' => '8001',
            'existing_snapshot' => [
                'title' => 'MUZDALIFAH JT PDG 12Hr JED',
                'arrival_city' => 'jeddah',
            ],
            'incoming_snapshot' => [
                'title' => 'MUZDALIFAH JT PDG 12Hr',
                'type' => 'umroh',
                'package_kind_id' => $kindId,
                'departure_city' => 'padang',
                'arrival_city' => null,
                'departure_date' => '2026-12-07',
                'duration_days' => 12,
                'airline' => 'Lion Air',
                'price_quad' => 38800000,
                'price_triple' => 40300000,
                'price_double' => 43400000,
                'seats_total' => 45,
                'seats_left' => 45,
            ],
            'diff_fields' => ['title', 'arrival_city'],
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.price-sync.apply', $run), [
                'change_ids' => [$change->id],
            ])
            ->assertRedirect(route('admin.price-sync.show', $run));

        $fresh = $package->fresh();
        $this->assertSame('MUZDALIFAH JT PDG 12Hr', $fresh->title);
        $this->assertNull($fresh->arrival_city);
    }

    #[Test]
    public function test_apply_changed_clears_arrival_city_when_incoming_is_null(): void
    {
        $kindId = $this->packageKindId('muzdalifah');

        $package = Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-clear-arrival',
            'source_key' => 'arminareka:7002',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
            'departure_city' => 'jakarta',
            'arrival_city' => 'jeddah',
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

        $run = PriceSyncRun::query()->create([
            'trigger' => PriceSyncRun::TRIGGER_MANUAL,
            'status' => PriceSyncRun::STATUS_WAITING,
            'started_at' => now(),
        ]);

        $change = $run->changes()->create([
            'package_id' => $package->id,
            'change_status' => PriceSyncChange::STATUS_CHANGED,
            'source_key' => 'arminareka:7002',
            'external_id' => '7002',
            'existing_snapshot' => [
                'title' => 'Muzdalifah JT CGK 12D JED',
                'arrival_city' => 'jeddah',
            ],
            'incoming_snapshot' => [
                'title' => 'Muzdalifah JT CGK 12D JED',
                'type' => 'umroh',
                'package_kind_id' => $kindId,
                'departure_city' => 'jakarta',
                'arrival_city' => null,
                'departure_date' => '2026-12-07',
                'duration_days' => 12,
                'airline' => 'Lion Air',
                'price_quad' => 38800000,
                'price_triple' => 40300000,
                'price_double' => 43400000,
                'seats_total' => 45,
                'seats_left' => 45,
            ],
            'diff_fields' => ['arrival_city'],
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.price-sync.apply', $run), [
                'change_ids' => [$change->id],
            ])
            ->assertRedirect(route('admin.price-sync.show', $run));

        $this->assertNull($package->fresh()->arrival_city);
    }

    #[Test]
    public function test_apply_new_restores_trashed_package_when_source_key_already_exists(): void
    {
        $kindId = $this->packageKindId('arafah');

        $package = Package::query()->create([
            'title' => 'AROFAH A GA CGK 9D MED',
            'slug' => 'arofah-trashed-apply',
            'source_key' => 'arminareka:5905',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
            'departure_city' => 'jakarta',
            'arrival_city' => 'madinah',
            'departure_date' => '2026-09-30',
            'duration_days' => 9,
            'price' => 43100000,
            'price_quad' => 43100000,
            'price_triple' => 45400000,
            'price_double' => 50000000,
            'airline' => 'Garuda Indonesia',
            'room_type' => 'quad',
            'seats_total' => 45,
            'seats_left' => 4,
            'status' => 'published',
            'images' => [],
        ]);
        $package->delete();

        $run = PriceSyncRun::query()->create([
            'trigger' => PriceSyncRun::TRIGGER_MANUAL,
            'status' => PriceSyncRun::STATUS_WAITING,
            'started_at' => now(),
        ]);

        $change = $run->changes()->create([
            'change_status' => PriceSyncChange::STATUS_NEW,
            'source_key' => 'arminareka:5905',
            'external_id' => '5905',
            'incoming_snapshot' => [
                'title' => 'AROFAH A GA CGK 9D MED',
                'type' => 'umroh',
                'package_kind_id' => $kindId,
                'departure_city' => 'jakarta',
                'arrival_city' => 'madinah',
                'departure_date' => '2026-09-30',
                'duration_days' => 9,
                'airline' => 'Garuda Indonesia',
                'price_quad' => 44000000,
                'price_triple' => 46000000,
                'price_double' => 51000000,
                'seats_total' => 45,
                'seats_left' => 3,
            ],
            'diff_fields' => [],
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.price-sync.apply', $run), [
                'change_ids' => [$change->id],
            ])
            ->assertRedirect(route('admin.price-sync.show', $run));

        $restored = Package::query()->where('source_key', 'arminareka:5905')->firstOrFail();
        $this->assertSame($package->id, $restored->id);
        $this->assertNull($restored->deleted_at);
        $this->assertSame(44000000, (int) $restored->price_quad);
        $this->assertSame(3, (int) $restored->seats_left);
        $this->assertSame(1, Package::withTrashed()->where('source_key', 'arminareka:5905')->count());
    }

    #[Test]
    public function test_admin_can_delete_sync_history(): void
    {
        config([
            'arminareka.jadwal.request_delay_ms' => 0,
            'arminareka.jadwal.page_size' => 15,
            'arminareka.jadwal.max_pages' => 2,
        ]);

        Http::fake(['*' => Http::response('<table><tbody></tbody></table>', 200)]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.price-sync.store'))
            ->assertRedirect();

        $run = PriceSyncRun::query()->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.price-sync.destroy', $run))
            ->assertRedirect(route('admin.price-sync.index'))
            ->assertSessionHas('ok');

        $this->actingAs($admin)
            ->get(route('admin.price-sync.index'))
            ->assertOk()
            ->assertSee('data-feedback-toast', false)
            ->assertDontSee('data-feedback-modal', false);

        $this->assertDatabaseMissing('price_sync_runs', ['id' => $run->id]);
        $this->assertDatabaseMissing('price_sync_changes', ['price_sync_run_id' => $run->id]);
    }

    #[Test]
    public function test_admin_error_feedback_still_uses_modal(): void
    {
        config([
            'arminareka.jadwal.request_delay_ms' => 0,
            'arminareka.jadwal.page_size' => 15,
            'arminareka.jadwal.max_pages' => 2,
        ]);

        Http::fake(['*' => Http::response('', 500)]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.price-sync.store'))
            ->assertRedirect(route('admin.price-sync.index'))
            ->assertSessionHas('err');

        $this->actingAs($admin)
            ->get(route('admin.price-sync.index'))
            ->assertOk()
            ->assertSee('data-feedback-modal', false)
            ->assertDontSee('data-feedback-toast', false);
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
