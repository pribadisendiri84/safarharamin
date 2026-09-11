<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Support\PriceListGrouper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListGrouperTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_it_sorts_packages_by_kind_duration_then_departure_within_airline(): void
    {
        $packages = collect([
            $this->makePackage('garuda-arafah-12', 'arafah', 12, 'Garuda Indonesia', '2026-12-20'),
            $this->makePackage('garuda-mina-9', 'mina', 9, 'Garuda Indonesia', '2026-11-15'),
            $this->makePackage('garuda-arafah-9', 'arafah', 9, 'Garuda Indonesia', '2026-11-10'),
            $this->makePackage('garuda-mina-12', 'mina', 12, 'Garuda Indonesia', '2026-12-01'),
            $this->makePackage('lion-arafah-9', 'arafah', 9, 'Lion Air', '2026-11-05'),
        ]);

        $groups = PriceListGrouper::group($packages);
        $garuda = $groups['Umroh Reguler']['Garuda Indonesia']->pluck('slug')->all();

        $this->assertSame([
            'garuda-arafah-9',
            'garuda-arafah-12',
            'garuda-mina-9',
            'garuda-mina-12',
        ], $garuda);

        $this->assertSame(['Garuda Indonesia', 'Lion Air'], $groups['Umroh Reguler']->keys()->all());
    }

    private function makePackage(
        string $slug,
        string $kindSlug,
        int $durationDays,
        string $airline,
        string $departureDate,
    ): Package {
        return Package::query()->create([
            'title' => strtoupper($slug),
            'slug' => $slug,
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId($kindSlug),
            'departure_city' => 'jakarta',
            'departure_date' => $departureDate,
            'duration_days' => $durationDays,
            'price' => 30000000,
            'price_quad' => 30000000,
            'airline' => $airline,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'status' => 'published',
            'images' => ['/images/placeholder-kaaba.svg'],
        ]);
    }
}
