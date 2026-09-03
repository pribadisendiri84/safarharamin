<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterTravelDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_hotels_and_airlines(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.hotels.store'), [
                'name' => 'Hotel Baru Makkah',
                'location' => Hotel::LOCATION_MAKKAH,
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.hotels.index', ['location' => Hotel::LOCATION_MAKKAH]));

        $this->actingAs($admin)
            ->post(route('admin.airlines.store'), [
                'name' => 'Citilink',
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.airlines.index'));

        $this->assertDatabaseHas('hotels', [
            'name' => 'Hotel Baru Makkah',
            'location' => Hotel::LOCATION_MAKKAH,
        ]);
        $this->assertDatabaseHas('airlines', ['name' => 'Citilink']);

        $this->actingAs($admin)
            ->get(route('admin.packages.create'))
            ->assertOk()
            ->assertSee('Hotel Baru Makkah', false)
            ->assertSee('Citilink', false);
    }

    public function test_package_form_renders_hotel_and_airline_dropdowns(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.operations.departures.create'))
            ->assertOk()
            ->assertSee('js-searchable', false)
            ->assertSee('Swissotel Makkah', false)
            ->assertSee('Garuda Indonesia', false);
    }
}
