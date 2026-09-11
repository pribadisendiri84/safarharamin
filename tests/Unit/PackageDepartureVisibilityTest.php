<?php

namespace Tests\Unit;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackageDepartureVisibilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_it_detects_past_departure_by_start_date(): void
    {
        $package = Package::query()->create([
            'title' => 'Past Umroh',
            'slug' => 'past-umroh',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->subDay()->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 10,
            'status' => 'published',
            'images' => [],
        ]);

        $this->assertTrue($package->isPastDeparture());
        $this->assertFalse($package->isPubliclyVisible());
    }

    #[Test]
    public function test_it_uses_end_date_for_range_display(): void
    {
        $package = Package::query()->create([
            'title' => 'Range Umroh',
            'slug' => 'range-umroh',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->subDays(10)->toDateString(),
            'departure_date_end' => now()->addDay()->toDateString(),
            'departure_date_display' => 'range',
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 10,
            'status' => 'published',
            'images' => [],
        ]);

        $this->assertFalse($package->isPastDeparture());
        $this->assertTrue($package->isPubliclyVisible());
    }

    #[Test]
    public function test_catalog_route_always_prefers_airport_code(): void
    {
        $this->assertSame('CGK', Package::catalogRouteCode('Jakarta', 'CGK'));
        $this->assertSame('SUB', Package::catalogRouteCode('Surabaya', 'SUB'));
        $this->assertSame('Surabaya', Package::catalogRouteCode('Surabaya', null));
    }

    #[Test]
    public function test_it_detects_expiring_soon_packages(): void
    {
        $soon = Package::query()->create([
            'title' => 'Soon Umroh',
            'slug' => 'soon-umroh',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->addDays(7)->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 10,
            'status' => 'published',
            'images' => [],
        ]);

        $later = Package::query()->create([
            'title' => 'Later Umroh',
            'slug' => 'later-umroh',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->addDays(30)->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 10,
            'status' => 'published',
            'images' => [],
        ]);

        $this->assertTrue($soon->isExpiringSoon());
        $this->assertFalse($later->isExpiringSoon());
        $this->assertSame(1, Package::query()->visibleOnCatalog()->expiringSoon()->count());
    }

    #[Test]
    public function test_it_detects_full_seats_by_status_or_zero_left(): void
    {
        $fullbook = Package::query()->create([
            'title' => 'Fullbook Umroh',
            'slug' => 'fullbook-umroh',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->addWeek()->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 0,
            'status' => 'fullbook',
            'images' => [],
        ]);

        $emptySeats = Package::query()->create([
            'title' => 'Empty Seats Umroh',
            'slug' => 'empty-seats-umroh',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->addWeek()->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 0,
            'status' => 'published',
            'images' => [],
        ]);

        $this->assertTrue($fullbook->isSeatsFull());
        $this->assertTrue($emptySeats->isSeatsFull());
    }

    #[Test]
    public function test_publicly_visible_scope_excludes_past_packages(): void
    {
        Package::query()->create([
            'title' => 'Past Umroh',
            'slug' => 'past-umroh-scope',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->subDay()->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 10,
            'status' => 'published',
            'images' => [],
        ]);

        Package::query()->create([
            'title' => 'Upcoming Umroh',
            'slug' => 'upcoming-umroh-scope',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => now()->addDay()->toDateString(),
            'duration_days' => 9,
            'price' => 30000000,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 10,
            'status' => 'published',
            'images' => [],
        ]);

        $visible = Package::query()->publiclyVisible()->pluck('slug')->all();

        $this->assertSame(['upcoming-umroh-scope'], $visible);
    }
}
