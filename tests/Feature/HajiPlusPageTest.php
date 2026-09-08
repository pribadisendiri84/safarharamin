<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Hotel;
use App\Models\User;
use App\Support\HajiPlusPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HajiPlusPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_haji_plus_page_editor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.haji-plus.edit'))
            ->assertOk()
            ->assertSee('Halaman Haji Plus')
            ->assertSee('Kartu kamar')
            ->assertSee('Hotel & maskapai')
            ->assertSee('Itinerary')
            ->assertSee('Quad');
    }

    public function test_admin_can_update_room_and_hotel_cards(): void
    {
        $user = User::factory()->create();
        $page = HajiPlusPage::content();

        $payload = $this->payloadFrom($page, [
            'rooms.0.label' => 'Quad Premium',
            'rooms.0.price_label' => 'Rp 275 Jt',
            'hotels.0.master_name' => 'Madinah Pullman',
            'hotels.0.master_location' => Hotel::LOCATION_MADINAH,
            'hotels.0.distance' => '±150 m dari Masjid Nabawi',
            'hotels.0.features_text' => "5★\nDekat masjid",
            'cta.title' => 'Siap berangkat bersama kami?',
        ]);

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), $payload)
            ->assertRedirect(route('admin.haji-plus.edit'));

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('Quad Premium')
            ->assertSee('Rp 275 Jt')
            ->assertSee('Madinah Pullman')
            ->assertSee('Siap berangkat bersama kami?')
            ->assertDontSee('4 paket');
    }

    public function test_admin_can_pick_master_airlines_and_hotels_for_haji_page(): void
    {
        $user = User::factory()->create();
        Airline::query()->where('name', 'Garuda Indonesia')->update(['logo' => '/storage/airlines/garuda.png']);
        Hotel::query()
            ->where('location', Hotel::LOCATION_MADINAH)
            ->where('name', 'Madinah Pullman')
            ->update(['logo' => '/storage/hotels/pullman.png']);

        $page = HajiPlusPage::content();
        $payload = $this->payloadFrom($page, [
            'partner_airlines' => ['Garuda Indonesia', 'Saudia'],
            'hotels.0.master_location' => Hotel::LOCATION_MADINAH,
            'hotels.0.master_name' => 'Madinah Pullman',
            'hotels.0.distance' => '±150 m dari Masjid Nabawi',
            'hotels.0.features_text' => "5★\nDekat masjid",
            'hotels.0.badge' => 'Hotel premium',
        ]);

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), $payload)
            ->assertRedirect(route('admin.haji-plus.edit'));

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('Madinah Pullman')
            ->assertSee('Garuda Indonesia / Saudia')
            ->assertSee('/storage/hotels/pullman.png', false)
            ->assertSee('/storage/airlines/garuda.png', false)
            ->assertSee('Saudia');
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadFrom(array $page, array $overrides): array
    {
        $payload = [
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
