<?php

namespace Tests\Feature;

use App\Models\Departure;
use App\Models\Hotel;
use App\Models\User;
use App\Support\HajiPlusPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HajiPageItineraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_haji_departure_with_itinerary_pdf(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $defaults = HajiPlusPage::departureDefaults();

        $this->actingAs($admin)
            ->post(route('admin.operations.departures.store'), [
                'source' => Departure::SOURCE_HAJI_PAGE,
                'program_name' => $defaults['program_name'],
                'program_kind' => 'haji',
                'departure_date' => '2026-09-11',
                'hijri_label' => '1448 H',
                'airline' => $defaults['airline'],
                'hotel_madinah' => $defaults['hotel_madinah'],
                'hotel_makkah' => $defaults['hotel_makkah'],
                'itinerary_pdf' => UploadedFile::fake()->create('itinerary.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.operations.departures.index', ['kind' => 'haji']));

        $departure = Departure::query()->first();
        $this->assertNotNull($departure);
        $this->assertSame('2026-09-11', $departure->departure_date->toDateString());
        $this->assertSame('1448 H', $departure->hijri_label);
        $this->assertStringStartsWith('/storage/haji-page-itineraries/', $departure->itinerary_pdf_path);
        $this->assertFalse($departure->show_on_haji_page);
    }

    public function test_haji_page_only_shows_itinerary_marked_visible_in_haji_plus_admin(): void
    {
        $visible = Departure::query()->create([
            'source' => Departure::SOURCE_HAJI_PAGE,
            'program_kind' => 'haji',
            'program_name' => 'Haji Khusus Arminareka — 1447H/2026M',
            'departure_date' => '2026-09-11',
            'hijri_label' => '1448 H',
            'itinerary_pdf_path' => '/storage/haji-page-itineraries/visible.pdf',
            'show_on_haji_page' => true,
            'program_snapshot' => HajiPlusPage::operationalSnapshot(),
        ]);

        Departure::query()->create([
            'source' => Departure::SOURCE_HAJI_PAGE,
            'program_kind' => 'haji',
            'program_name' => 'Haji Khusus Arminareka — 1447H/2026M',
            'departure_date' => '2026-10-01',
            'hijri_label' => '1448 H',
            'itinerary_pdf_path' => '/storage/haji-page-itineraries/hidden.pdf',
            'show_on_haji_page' => false,
            'program_snapshot' => HajiPlusPage::operationalSnapshot(),
        ]);

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('Itinerary')
            ->assertSee('Keberangkatan 11 September 2026')
            ->assertSee('/storage/haji-page-itineraries/visible.pdf', false)
            ->assertDontSee('/storage/haji-page-itineraries/hidden.pdf', false)
            ->assertDontSee('Keberangkatan 01 Oktober 2026');
    }

    public function test_admin_can_toggle_itinerary_visibility_from_haji_plus_tab(): void
    {
        $user = User::factory()->create();
        $page = HajiPlusPage::content();

        $departure = Departure::query()->create([
            'source' => Departure::SOURCE_HAJI_PAGE,
            'program_kind' => 'haji',
            'program_name' => 'Haji Khusus Arminareka — 1447H/2026M',
            'departure_date' => '2026-09-11',
            'hijri_label' => '1448 H',
            'itinerary_pdf_path' => '/storage/haji-page-itineraries/toggle.pdf',
            'show_on_haji_page' => false,
            'program_snapshot' => HajiPlusPage::operationalSnapshot(),
        ]);

        $payload = $this->hajiPlusPayload($page, [
            'itinerary_visible_departures' => [$departure->id],
        ]);

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), $payload)
            ->assertRedirect(route('admin.haji-plus.edit'));

        $this->assertTrue($departure->fresh()->show_on_haji_page);

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('Keberangkatan 11 September 2026');
    }

    public function test_departures_index_has_haji_and_umroh_tabs(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.operations.departures.index'))
            ->assertOk()
            ->assertSee('tabs--kind', false)
            ->assertSee('Umroh')
            ->assertSee('Haji');

        $this->actingAs($admin)
            ->get(route('admin.operations.departures.index', ['kind' => 'haji']))
            ->assertOk()
            ->assertSee('Haji Plus diisi dari data halaman khusus');
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function hajiPlusPayload(array $page, array $overrides = []): array
    {
        $payload = [
            'hero' => [
                'badge' => $page['hero']['badge'],
                'season' => $page['hero']['season'],
                'title' => $page['hero']['title'],
                'subtitle' => $page['hero']['subtitle'],
                'starting_price' => $page['hero']['starting_price'],
                'show_quota' => $page['hero']['show_quota'] === '1' ? '1' : null,
                'image' => $page['hero']['image'],
            ],
            'rooms' => array_map(fn (array $room) => [
                'label' => $room['label'],
                'occupancy' => $room['occupancy'],
                'price_label' => $room['price_label'],
                'price_note' => $room['price_note'],
                'is_featured' => $room['is_featured'] === '1' ? '1' : null,
            ], $page['rooms']),
            'benefits' => $page['benefits'],
            'partner_airlines' => $page['partner_airlines'],
            'hotels' => array_map(fn (array $hotel) => [
                'master_name' => $hotel['master_name'] ?? '',
                'master_location' => $hotel['master_location'] ?? Hotel::LOCATION_MADINAH,
                'distance' => $hotel['distance'],
                'features_text' => $hotel['features_text'],
                'badge' => $hotel['badge'],
            ], $page['hotels']),
            'airline' => [
                'description' => $page['airline']['description'],
                'points_text' => $page['airline']['points_text'],
            ],
            'flow' => $page['flow'],
            'cta' => $page['cta'],
        ];

        foreach ($overrides as $path => $value) {
            data_set($payload, $path, $value);
        }

        return $payload;
    }
}
