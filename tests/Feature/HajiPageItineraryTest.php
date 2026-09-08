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

    public function test_admin_can_upload_official_and_sample_itineraries(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $page = HajiPlusPage::content();

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), [
                ...$this->payloadFrom($page),
                'official_itinerary_departure_dates' => ['2026-11-15'],
                'official_itinerary_pdfs' => [
                    UploadedFile::fake()->create('official.pdf', 120, 'application/pdf'),
                ],
                'sample_itinerary_labels' => ['Haji 1445'],
                'sample_itinerary_pdfs' => [
                    UploadedFile::fake()->create('sample.pdf', 120, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('admin.haji-plus.edit'));

        $official = HajiPageItinerary::query()->official()->get();
        $sample = HajiPageItinerary::query()->sample()->get();

        $this->assertCount(1, $official);
        $this->assertSame('2026-11-15', $official[0]->departure_date->toDateString());
        $this->assertStringStartsWith('/storage/haji-page-itineraries/', $official[0]->file_path);

        $this->assertCount(1, $sample);
        $this->assertSame('Haji 1445', $sample[0]->label);
    }

    public function test_haji_page_shows_official_and_sample_itineraries_like_package_detail(): void
    {
        HajiPageItinerary::query()->create([
            'kind' => HajiPageItinerary::KIND_OFFICIAL,
            'departure_date' => '2026-11-15',
            'file_path' => '/storage/haji-page-itineraries/official.pdf',
        ]);
        HajiPageItinerary::query()->create([
            'kind' => HajiPageItinerary::KIND_SAMPLE,
            'label' => 'Haji 1445',
            'file_path' => '/storage/haji-page-itineraries/sample-1445.pdf',
        ]);

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('Itinerary musim ini')
            ->assertSee('Contoh itinerary')
            ->assertSee('15 November 2026')
            ->assertSee('Haji 1445')
            ->assertSee('itinerary-pdf-trigger', false)
            ->assertSee('itinerary-pdf-modal', false)
            ->assertSee('/storage/haji-page-itineraries/official.pdf', false)
            ->assertSee('/storage/haji-page-itineraries/sample-1445.pdf', false);
    }

    public function test_operational_snapshot_includes_official_itineraries_only(): void
    {
        HajiPageItinerary::query()->create([
            'kind' => HajiPageItinerary::KIND_OFFICIAL,
            'departure_date' => '2026-11-15',
            'file_path' => '/storage/haji-page-itineraries/official.pdf',
        ]);
        HajiPageItinerary::query()->create([
            'kind' => HajiPageItinerary::KIND_SAMPLE,
            'label' => 'Haji 1445',
            'file_path' => '/storage/haji-page-itineraries/sample-1445.pdf',
        ]);

        $snapshot = HajiPlusPage::operationalSnapshot();

        $this->assertCount(1, $snapshot['official_itineraries']);
        $this->assertSame('2026-11-15', $snapshot['official_itineraries'][0]['departure_date']);
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
