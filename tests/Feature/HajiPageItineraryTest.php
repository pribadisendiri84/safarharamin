<?php

namespace Tests\Feature;

use App\Models\HajiPageItinerary;
use App\Models\User;
use App\Support\HajiPlusPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HajiPageItineraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_itinerary_with_hijri_label(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $page = HajiPlusPage::content();

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), [
                ...$this->payloadFrom($page),
                'itinerary_departure_dates' => ['2026-09-11'],
                'itinerary_hijri_labels' => ['1448 H'],
                'itinerary_pdfs' => [
                    UploadedFile::fake()->create('itinerary.pdf', 120, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('admin.haji-plus.edit'));

        $item = HajiPageItinerary::query()->first();
        $this->assertNotNull($item);
        $this->assertSame('2026-09-11', $item->departure_date->toDateString());
        $this->assertSame('1448 H', $item->hijri_label);
        $this->assertStringStartsWith('/storage/haji-page-itineraries/', $item->file_path);
    }

    public function test_haji_page_shows_itinerary_with_departure_and_hijri_labels(): void
    {
        HajiPageItinerary::query()->create([
            'kind' => 'official',
            'departure_date' => '2026-09-11',
            'hijri_label' => '1448 H',
            'file_path' => '/storage/haji-page-itineraries/official.pdf',
        ]);

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('Itinerary')
            ->assertSee('Keberangkatan 11 September 2026')
            ->assertSee('1448 H')
            ->assertSee('itinerary-pdf-trigger', false)
            ->assertSee('itinerary-pdf-modal', false)
            ->assertSee('/storage/haji-page-itineraries/official.pdf', false)
            ->assertDontSee('Contoh itinerary');
    }

    public function test_operational_snapshot_includes_itineraries_with_hijri_label(): void
    {
        HajiPageItinerary::query()->create([
            'kind' => 'official',
            'departure_date' => '2026-09-11',
            'hijri_label' => '1448 H',
            'file_path' => '/storage/haji-page-itineraries/official.pdf',
        ]);

        $snapshot = HajiPlusPage::operationalSnapshot();

        $this->assertCount(1, $snapshot['official_itineraries']);
        $this->assertSame('2026-09-11', $snapshot['official_itineraries'][0]['departure_date']);
        $this->assertSame('1448 H', $snapshot['official_itineraries'][0]['hijri_label']);
        $this->assertSame('/storage/haji-page-itineraries/official.pdf', $snapshot['official_itineraries'][0]['file_path']);
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    private function payloadFrom(array $page): array
    {
        return [
            'hero' => [
                'badge' => $page['hero']['badge'],
                'season' => $page['hero']['season'],
                'title' => $page['hero']['title'],
                'subtitle' => $page['hero']['subtitle'],
                'starting_price' => $page['hero']['starting_price'],
                'show_quota' => $page['hero']['show_quota'] === '1' ? '1' : null,
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
                'master_location' => $hotel['master_location'] ?? 'madinah',
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
    }
}
