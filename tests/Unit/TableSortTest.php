<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Support\TableSort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TableSortTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_it_sorts_packages_by_title_ascending(): void
    {
        Package::query()->create([
            'title' => 'Zulu Paket',
            'slug' => 'zulu-paket',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-07',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'airline' => 'Garuda Indonesia',
            'room_type' => 'quad',
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'published',
            'images' => [],
        ]);

        Package::query()->create([
            'title' => 'Alpha Paket',
            'slug' => 'alpha-paket',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-08',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'airline' => 'Garuda Indonesia',
            'room_type' => 'quad',
            'seats_total' => 45,
            'seats_left' => 45,
            'status' => 'published',
            'images' => [],
        ]);

        $request = Request::create('/admin/packages', 'GET', [
            'sort' => 'title',
            'dir' => 'asc',
        ]);

        $query = Package::query();
        TableSort::apply($query, $request, ['title' => 'title'], 'updated_at', 'desc');

        $this->assertSame('Alpha Paket', $query->pluck('title')->first());
    }
}
