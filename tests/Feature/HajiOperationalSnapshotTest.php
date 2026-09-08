<?php

namespace Tests\Feature;

use App\Models\Departure;
use App\Models\Inquiry;
use App\Models\User;
use App\Support\HajiPlusPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HajiOperationalSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_haji_departure_from_page_with_snapshot(): void
    {
        $admin = User::factory()->admin()->create();
        $page = HajiPlusPage::content();
        $stored = HajiPlusPage::stored();
        $payload = array_replace_recursive($stored ?: [
            'hero' => $page['hero'],
            'rooms' => $page['rooms'],
            'benefits' => $page['benefits'],
            'hotels' => array_map(fn (array $hotel) => [
                'master_name' => $hotel['master_name'] ?? '',
                'master_location' => $hotel['master_location'] ?? '',
                'distance' => $hotel['distance'] ?? '',
                'features_text' => $hotel['features_text'] ?? '',
                'badge' => $hotel['badge'] ?? '',
            ], $page['hotels']),
            'partner_airlines' => $page['partner_airlines'],
            'airline' => [
                'description' => $page['airline']['description'],
                'points_text' => $page['airline']['points_text'],
            ],
            'flow' => $page['flow'],
            'cta' => $page['cta'],
        ], [
            'rooms' => array_map(fn (array $room, int $index) => array_merge($room, [
                'price' => $index === 0 ? 18_000 : ($room['price'] ?? 0),
            ]), $page['rooms'], array_keys($page['rooms'])),
        ]);
        HajiPlusPage::save($payload);

        \App\Support\HajiExchangeRate::saveManual(16_500, 'USD', true, \App\Support\HajiExchangeRate::MODE_MANUAL);

        $defaults = HajiPlusPage::departureDefaults();

        $this->actingAs($admin)
            ->get(route('admin.operations.departures.create', ['from' => 'haji_page']))
            ->assertOk()
            ->assertSee($defaults['program_name'])
            ->assertSee('Data operasional Haji Plus dari halaman khusus');

        $this->actingAs($admin)
            ->post(route('admin.operations.departures.store'), [
                'source' => Departure::SOURCE_HAJI_PAGE,
                'program_name' => $defaults['program_name'],
                'program_kind' => 'haji',
                'airline' => $defaults['airline'],
                'hotel_madinah' => $defaults['hotel_madinah'],
                'hotel_makkah' => $defaults['hotel_makkah'],
            ])
            ->assertRedirect(route('admin.operations.departures.index', ['kind' => 'haji']));

        $departure = Departure::query()->first();
        $this->assertNotNull($departure);
        $this->assertSame('haji', $departure->program_kind);
        $this->assertSame(Departure::SOURCE_HAJI_PAGE, $departure->source);
        $this->assertNull($departure->package_id);
        $this->assertSame(18_000, (int) ($departure->program_snapshot['rooms'][0]['price'] ?? 0));
        $this->assertSame('$18.000', $departure->program_snapshot['rooms'][0]['price_label'] ?? null);
        $this->assertSame(297_000_000, (int) ($departure->program_snapshot['rooms'][0]['price_idr_estimate'] ?? 0));
    }

    public function test_haji_inquiry_import_matches_haji_departure_without_package(): void
    {
        $admin = User::factory()->admin()->create();

        $departure = Departure::query()->create([
            'source' => Departure::SOURCE_HAJI_PAGE,
            'program_name' => 'Haji Khusus Arminareka — 1447H/2026M',
            'program_kind' => 'haji',
            'airline' => 'Garuda Indonesia · Saudia',
            'program_snapshot' => HajiPlusPage::operationalSnapshot(),
        ]);

        $inquiry = Inquiry::query()->create([
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_WEBSITE,
            'program_kind' => 'haji',
            'name' => 'Ahmad',
            'phone' => '08123456789',
            'pax' => 2,
            'status' => Inquiry::STATUS_SOLD,
            'sold_pax' => 2,
            'sold_amount' => 560000000,
            'closed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee($departure->program_name);
    }

    public function test_haji_register_creates_inquiry_without_package(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Budi Haji',
            'phone' => '08111222333',
            'city' => 'jakarta',
            'program_kind' => 'haji',
            'pax' => 1,
            'notes' => 'Minat program Haji Plus.',
        ])->assertRedirect(route('register'));

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Budi Haji',
            'program_kind' => 'haji',
            'package_id' => null,
            'kind' => 'daftar',
        ]);
    }
}
