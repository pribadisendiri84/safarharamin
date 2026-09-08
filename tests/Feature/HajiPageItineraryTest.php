<?php

namespace Tests\Feature;

use App\Models\Departure;
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
    }

    public function test_haji_page_shows_itinerary_from_haji_departures(): void
    {
        Departure::query()->create([
            'source' => Departure::SOURCE_HAJI_PAGE,
            'program_kind' => 'haji',
            'program_name' => 'Haji Khusus Arminareka — 1447H/2026M',
            'departure_date' => '2026-09-11',
            'hijri_label' => '1448 H',
            'itinerary_pdf_path' => '/storage/haji-page-itineraries/official.pdf',
            'program_snapshot' => HajiPlusPage::operationalSnapshot(),
        ]);

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('Itinerary')
            ->assertSee('Keberangkatan 11 September 2026')
            ->assertSee('1448 H')
            ->assertSee('itinerary-pdf-trigger', false)
            ->assertSee('/storage/haji-page-itineraries/official.pdf', false);
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
}
