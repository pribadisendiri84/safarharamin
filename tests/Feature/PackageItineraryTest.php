<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageItinerary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PackageItineraryTest extends TestCase
{
    use RefreshDatabase;

    private function packagePayload(Package $package, array $overrides = []): array
    {
        return array_merge([
            'title' => $package->title,
            'type' => $package->type,
            'package_kind_id' => $package->package_kind_id ?? $this->packageKindId(),
            'departure_city' => $package->departure_city,
            'departure_date' => optional($package->departure_date)->format('Y-m-d'),
            'departure_date_display' => $package->departure_date_display ?? 'single',
            'duration_days' => $package->duration_days,
            'price_quad' => $package->price_quad ?? $package->price,
            'price_triple' => $package->price_triple,
            'price_double' => $package->price_double,
            'seats_total' => $package->seats_total,
            'seats_left' => $package->seats_left,
            'status' => $package->status,
        ], $overrides);
    }

    public function test_admin_can_upload_multiple_itinerary_pdfs(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $package = Package::query()->create([
            'title' => 'Umroh Itinerary PDF',
            'slug' => 'umroh-itinerary-pdf',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-10-12',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => ['/images/placeholder-kaaba.svg'],
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.packages.update', $package), [
                '_method' => 'PUT',
                ...$this->packagePayload($package),
                'itinerary_departure_dates' => [
                    '2026-11-15',
                    '2026-12-01',
                ],
                'itinerary_pdfs' => [
                    UploadedFile::fake()->create('itinerary-nov.pdf', 120, 'application/pdf'),
                    UploadedFile::fake()->create('itinerary-dec.pdf', 120, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('admin.packages.index'));

        $items = PackageItinerary::query()->where('package_id', $package->id)->orderBy('departure_date')->get();
        $this->assertCount(2, $items);
        $this->assertSame('2026-11-15', $items[0]->departure_date->toDateString());
        $this->assertSame('2026-12-01', $items[1]->departure_date->toDateString());
        $this->assertStringStartsWith('/storage/package-itineraries/', $items[0]->file_path);
    }

    public function test_package_detail_shows_itinerary_links_by_departure_date(): void
    {
        $package = Package::query()->create([
            'title' => 'Umroh Detail Itinerary',
            'slug' => 'umroh-detail-itinerary',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-10-12',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => ['/images/placeholder-kaaba.svg'],
            'status' => 'published',
        ]);

        PackageItinerary::query()->create([
            'package_id' => $package->id,
            'departure_date' => '2026-11-15',
            'file_path' => '/storage/package-itineraries/sample-itinerary.pdf',
        ]);

        $this->get(route('packages.show', $package))
            ->assertOk()
            ->assertSee('Itinerary')
            ->assertSee('15 November 2026')
            ->assertSee('itinerary-pdf-trigger', false)
            ->assertSee('itinerary-pdf-modal', false)
            ->assertSee('Download')
            ->assertSee('/storage/package-itineraries/sample-itinerary.pdf', false);
    }

    public function test_package_detail_lists_itineraries_sorted_by_departure_date(): void
    {
        $package = Package::query()->create([
            'title' => 'Umroh Sort Itinerary',
            'slug' => 'umroh-sort-itinerary',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-10-12',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => ['/images/placeholder-kaaba.svg'],
            'status' => 'published',
        ]);

        PackageItinerary::query()->create([
            'package_id' => $package->id,
            'departure_date' => '2026-12-01',
            'file_path' => '/storage/package-itineraries/dec.pdf',
            'sort_order' => 2,
        ]);
        PackageItinerary::query()->create([
            'package_id' => $package->id,
            'departure_date' => '2026-11-15',
            'file_path' => '/storage/package-itineraries/nov.pdf',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('packages.show', $package))->assertOk();
        $response->assertSeeInOrder([
            '15 November 2026',
            '01 Desember 2026',
        ]);
    }
}
