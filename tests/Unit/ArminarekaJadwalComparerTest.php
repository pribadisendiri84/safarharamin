<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Models\PriceSyncChange;
use App\Services\Arminareka\ArminarekaJadwalComparer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArminarekaJadwalComparerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_it_detects_price_update_when_title_and_date_match(): void
    {
        $kindId = $this->packageKindId('muzdalifah');

        Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-sync-old',
            'source_key' => 'arminareka:100',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
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
            'status' => 'draft',
            'images' => [],
            'facilities' => ['Tiket PP'],
        ]);

        Package::query()->create([
            'title' => 'Mina GA CGK 9D JED',
            'slug' => 'mina-removed',
            'source_key' => 'arminareka:200',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId('mina'),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-11-30',
            'duration_days' => 9,
            'price' => 38800000,
            'price_quad' => 38800000,
            'price_triple' => 40400000,
            'price_double' => 43700000,
            'airline' => 'Garuda Indonesia',
            'room_type' => 'quad',
            'seats_total' => 90,
            'seats_left' => 90,
            'status' => 'published',
            'images' => [],
        ]);

        $incoming = [
            [
                'external_id' => '100',
                'source_key' => 'arminareka:100',
                'periode_full' => 'Muzdalifah JT CGK 12D JED (07/12/2026)',
                'title' => 'Muzdalifah JT CGK 12D JED',
                'type' => 'umroh',
                'package_kind_id' => $kindId,
                'departure_city' => 'jakarta',
                'departure_date' => '2026-12-07',
                'duration_days' => 12,
                'airline' => 'Lion Air',
                'price_quad' => 39000000,
                'price_triple' => 40300000,
                'price_double' => 43400000,
                'seats_total' => 45,
                'seats_left' => 40,
                'status' => 'draft',
                'warnings' => [],
                'raw' => [],
            ],
            [
                'external_id' => '300',
                'source_key' => 'arminareka:300',
                'periode_full' => 'AROFAH A GA CGK 12D MED (05/12/2026)',
                'title' => 'AROFAH A GA CGK 12D MED',
                'type' => 'umroh',
                'package_kind_id' => $this->packageKindId('arafah'),
                'departure_city' => 'jakarta',
                'departure_date' => '2026-12-05',
                'duration_days' => 12,
                'airline' => 'Garuda Indonesia',
                'price_quad' => 49600000,
                'price_triple' => 52900000,
                'price_double' => 59500000,
                'seats_total' => 44,
                'seats_left' => 42,
                'status' => 'draft',
                'warnings' => [],
                'raw' => [],
            ],
        ];

        $changes = (new ArminarekaJadwalComparer)->compare($incoming);

        $this->assertSame(PriceSyncChange::STATUS_CHANGED, collect($changes)->firstWhere('external_id', '100')['change_status']);
        $this->assertSame(PriceSyncChange::STATUS_NEW, collect($changes)->firstWhere('external_id', '300')['change_status']);
        $this->assertSame(PriceSyncChange::STATUS_REMOVED, collect($changes)->firstWhere('external_id', '200')['change_status']);
    }

    #[Test]
    public function test_it_detects_new_departure_when_title_matches_but_date_differs(): void
    {
        $kindId = $this->packageKindId('muzdalifah');

        Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-old-date',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
            'departure_city' => 'jakarta',
            'departure_date' => '2026-11-30',
            'duration_days' => 12,
            'price' => 38800000,
            'price_quad' => 38800000,
            'airline' => 'Lion Air',
            'room_type' => 'quad',
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'published',
            'images' => ['/images/flyers/umroh1.png'],
            'facilities' => ['Tiket PP'],
            'hotel_makkah' => 'Hilton',
        ]);

        $incoming = [[
            'external_id' => '999',
            'source_key' => 'arminareka:999',
            'periode_full' => 'Muzdalifah JT CGK 12D JED (07/12/2026)',
            'title' => 'Muzdalifah JT CGK 12D JED',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-07',
            'duration_days' => 12,
            'airline' => 'Lion Air',
            'price_quad' => 39000000,
            'price_triple' => 40300000,
            'price_double' => 43400000,
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'draft',
            'warnings' => [],
            'raw' => [],
        ]];

        $change = (new ArminarekaJadwalComparer)->compare($incoming)[0];

        $this->assertSame(PriceSyncChange::STATUS_NEW_DEPARTURE, $change['change_status']);
        $this->assertSame('Muzdalifah JT CGK 12D JED', $change['existing_snapshot']['title']);
        $this->assertSame('2026-11-30', $change['existing_snapshot']['departure_date']);
        $this->assertSame('2026-12-07', $change['incoming_snapshot']['departure_date']);
    }

    #[Test]
    public function test_it_marks_changed_when_title_and_date_match_but_source_key_is_missing(): void
    {
        $kindId = $this->packageKindId('muzdalifah');

        Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-no-source-key',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
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

        $incoming = [[
            'external_id' => '5928',
            'source_key' => 'arminareka:5928',
            'periode_full' => 'Muzdalifah JT CGK 12D JED (07/12/2026)',
            'title' => 'Muzdalifah JT CGK 12D JED',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-07',
            'duration_days' => 12,
            'airline' => 'Lion Air',
            'price_quad' => 38800000,
            'price_triple' => 40300000,
            'price_double' => 43400000,
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'published',
            'warnings' => [],
            'raw' => [],
        ]];

        $change = (new ArminarekaJadwalComparer)->compare($incoming)[0];

        $this->assertSame(PriceSyncChange::STATUS_CHANGED, $change['change_status']);
        $this->assertContains('source_key', $change['diff_fields']);
        $this->assertNull($change['existing_snapshot']['source_key']);
        $this->assertSame('arminareka:5928', $change['incoming_snapshot']['source_key']);
    }

    #[Test]
    public function test_it_marks_changed_when_title_and_date_match_but_source_key_is_stale(): void
    {
        $kindId = $this->packageKindId('muzdalifah');

        Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-stale-source-key',
            'source_key' => 'arminareka:100',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
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

        $incoming = [[
            'external_id' => '999',
            'source_key' => 'arminareka:999',
            'periode_full' => 'Muzdalifah JT CGK 12D JED (07/12/2026)',
            'title' => 'Muzdalifah JT CGK 12D JED',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-07',
            'duration_days' => 12,
            'airline' => 'Lion Air',
            'price_quad' => 38800000,
            'price_triple' => 40300000,
            'price_double' => 43400000,
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'published',
            'warnings' => [],
            'raw' => [],
        ]];

        $changes = (new ArminarekaJadwalComparer)->compare($incoming);

        $this->assertCount(1, $changes);
        $change = $changes[0];
        $this->assertSame(PriceSyncChange::STATUS_CHANGED, $change['change_status']);
        $this->assertContains('source_key', $change['diff_fields']);
        $this->assertSame('arminareka:100', $change['existing_snapshot']['source_key']);
        $this->assertSame('arminareka:999', $change['incoming_snapshot']['source_key']);
        $this->assertSame('arminareka:999', $change['source_key']);
    }

    #[Test]
    public function test_it_detects_departure_date_change_when_source_key_matches(): void
    {
        $kindId = $this->packageKindId('muzdalifah');

        Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-manual-date-change',
            'source_key' => 'arminareka:5924',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-20',
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

        $incoming = [[
            'external_id' => '5924',
            'source_key' => 'arminareka:5924',
            'periode_full' => 'Muzdalifah JT CGK 12D JED (30/11/2026)',
            'title' => 'Muzdalifah JT CGK 12D JED',
            'type' => 'umroh',
            'package_kind_id' => $kindId,
            'departure_city' => 'jakarta',
            'departure_date' => '2026-11-30',
            'duration_days' => 12,
            'airline' => 'Lion Air',
            'price_quad' => 38800000,
            'price_triple' => 40300000,
            'price_double' => 43400000,
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'published',
            'warnings' => [],
            'raw' => [],
        ]];

        $change = (new ArminarekaJadwalComparer)->compare($incoming)[0];

        $this->assertSame(PriceSyncChange::STATUS_CHANGED, $change['change_status']);
        $this->assertContains('departure_date', $change['diff_fields']);
        $this->assertSame('2026-12-20', $change['existing_snapshot']['departure_date']);
        $this->assertSame('2026-11-30', $change['incoming_snapshot']['departure_date']);
    }

    #[Test]
    public function test_it_detects_title_change_when_source_key_matches(): void
    {
        $kindId = $this->packageKindId('muzdalifah');

        Package::query()->create([
            'title' => 'MUZDALIFAH JT PDG 12Hr JED',
            'slug' => 'muzdalifah-title-change',
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

        $incoming = [[
            'external_id' => '8001',
            'source_key' => 'arminareka:8001',
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
            'warnings' => [],
            'raw' => [],
        ]];

        $change = (new ArminarekaJadwalComparer)->compare($incoming)[0];

        $this->assertSame(PriceSyncChange::STATUS_CHANGED, $change['change_status']);
        $this->assertContains('title', $change['diff_fields']);
        $this->assertContains('arrival_city', $change['diff_fields']);
        $this->assertSame('MUZDALIFAH JT PDG 12Hr JED', $change['existing_snapshot']['title']);
        $this->assertSame('MUZDALIFAH JT PDG 12Hr', $change['incoming_snapshot']['title']);
    }

    #[Test]
    public function test_it_detects_arrival_city_cleared_when_incoming_has_no_destination(): void
    {
        $kindId = $this->packageKindId('muzdalifah');

        Package::query()->create([
            'title' => 'Muzdalifah JT CGK 12D JED',
            'slug' => 'muzdalifah-clear-arrival-compare',
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

        $incoming = [[
            'external_id' => '7002',
            'source_key' => 'arminareka:7002',
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
            'warnings' => [],
            'raw' => [],
        ]];

        $change = (new ArminarekaJadwalComparer)->compare($incoming)[0];

        $this->assertSame(PriceSyncChange::STATUS_CHANGED, $change['change_status']);
        $this->assertContains('arrival_city', $change['diff_fields']);
        $this->assertSame('jeddah', $change['existing_snapshot']['arrival_city']);
        $this->assertNull($change['incoming_snapshot']['arrival_city']);
    }

    #[Test]
    public function test_it_matches_trashed_package_by_source_key_instead_of_marking_new(): void
    {
        $kindId = $this->packageKindId('arafah');

        $package = Package::query()->create([
            'title' => 'AROFAH A GA CGK 9D MED',
            'slug' => 'arofah-trashed-sync',
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

        $incoming = [[
            'external_id' => '5905',
            'source_key' => 'arminareka:5905',
            'periode_full' => 'AROFAH A GA CGK 9D MED (30/09/2026)',
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
            'status' => 'published',
            'warnings' => [],
            'raw' => [],
        ]];

        $change = (new ArminarekaJadwalComparer)->compare($incoming)[0];

        $this->assertSame($package->id, $change['package_id']);
        $this->assertSame(PriceSyncChange::STATUS_CHANGED, $change['change_status']);
        $this->assertContains('price_quad', $change['diff_fields']);
    }
}
