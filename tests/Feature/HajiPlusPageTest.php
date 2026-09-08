<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Hotel;
use App\Models\User;
use App\Support\HajiPlusPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('Detail program')
            ->assertSee('Quad');
    }

    public function test_admin_can_update_room_and_hotel_cards(): void
    {
        $user = User::factory()->create();
        $page = HajiPlusPage::content();

        $payload = $this->payloadFrom($page, [
            'rooms.0.label' => 'Quad Premium',
            'rooms.0.price' => 18_000,
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
            ->assertSee('$18.000')
            ->assertSee('Madinah Pullman')
            ->assertSee('Siap berangkat bersama kami?')
            ->assertDontSee('4 paket');
    }

    public function test_admin_can_update_detail_program_section(): void
    {
        $user = User::factory()->create();
        $page = HajiPlusPage::content();

        $payload = $this->payloadFrom($page, [
            'detail_program.deposit_summary' => 'Setoran awal porsi USD 4.500 + DP USD 600',
            'detail_program.deposit_idr_note' => '~Rp 6 juta',
            'detail_program.faq.0.question' => 'Berapa setoran awal?',
            'detail_program.faq.0.answer' => 'USD 4.500 + DP USD 600 (~Rp 6 juta).',
        ]);

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), $payload)
            ->assertRedirect(route('admin.haji-plus.edit'));

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('Setoran awal: Setoran awal porsi USD 4.500 + DP USD 600 (~Rp 6 juta)')
            ->assertSee('Berapa setoran awal?')
            ->assertSee('USD 4.500 + DP USD 600 (~Rp 6 juta).');
    }

    public function test_admin_can_upload_hero_background_without_auto_selecting_it(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $page = HajiPlusPage::content();
        $payload = $this->payloadFrom($page, []);
        $defaultImage = HajiPlusPage::defaultHeroImage();

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), [
                ...$payload,
                'hero' => [
                    ...$payload['hero'],
                    'image' => $defaultImage,
                    'image_file' => UploadedFile::fake()->image('haji-hero.jpg', 1600, 900),
                ],
            ])
            ->assertRedirect(route('admin.haji-plus.edit'));

        $hero = HajiPlusPage::content()['hero'];
        $this->assertCount(1, $hero['images']);
        $this->assertSame($defaultImage, $hero['image']);

        $uploaded = $hero['images'][0];

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), [
                ...$payload,
                'hero' => [
                    ...$payload['hero'],
                    'image' => $uploaded,
                ],
            ])
            ->assertRedirect(route('admin.haji-plus.edit'));

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee("--haji-hero-image: url('{$uploaded}')", false);
    }

    public function test_admin_can_delete_unused_hero_background_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $page = HajiPlusPage::content();
        $payload = $this->payloadFrom($page, []);

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), [
                ...$payload,
                'hero' => [
                    ...$payload['hero'],
                    'image_file' => UploadedFile::fake()->image('haji-hero.jpg', 1600, 900),
                ],
            ]);

        $uploaded = HajiPlusPage::content()['hero']['images'][0];

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), [
                ...$payload,
                'hero' => [
                    ...$payload['hero'],
                    'image' => HajiPlusPage::defaultHeroImage(),
                ],
                'delete_hero_images' => [$uploaded],
            ])
            ->assertRedirect(route('admin.haji-plus.edit'));

        $hero = HajiPlusPage::content()['hero'];
        $this->assertSame([], $hero['images']);
        $this->assertSame(HajiPlusPage::defaultHeroImage(), $hero['image']);
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
            ->assertSee('Garuda Indonesia · Saudia')
            ->assertSee('/storage/hotels/pullman.png', false)
            ->assertSee('/storage/airlines/garuda.png', false)
            ->assertSee('Saudia');
    }

    public function test_admin_can_hide_airline_names_and_show_logos_only(): void
    {
        $user = User::factory()->create();
        Airline::query()->where('name', 'Garuda Indonesia')->update(['logo' => '/storage/airlines/garuda.png']);
        Airline::query()->where('name', 'Saudia')->update(['logo' => '/storage/airlines/saudia.png']);
        Airline::query()->where('name', 'Emirates')->update(['logo' => '/storage/airlines/emirates.png']);

        $page = HajiPlusPage::content();
        $payload = $this->payloadFrom($page, [
            'partner_airlines' => ['Garuda Indonesia', 'Saudia', 'Emirates'],
            'airline.show_names' => null,
        ]);

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), $payload)
            ->assertRedirect(route('admin.haji-plus.edit'));

        $this->assertSame('0', HajiPlusPage::content()['airline']['show_names']);

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertDontSee('Garuda Indonesia · Saudia · Emirates')
            ->assertSee('/storage/airlines/garuda.png', false)
            ->assertSee('/storage/airlines/saudia.png', false)
            ->assertSee('/storage/airlines/emirates.png', false)
            ->assertSee('haji-airline-logos--primary', false);
    }

    public function test_haji_room_shows_idr_estimate_when_usd_rate_enabled(): void
    {
        \App\Support\HajiExchangeRate::saveManual(16_500, 'USD', true, \App\Support\HajiExchangeRate::MODE_MANUAL);

        $user = User::factory()->create();
        $page = HajiPlusPage::content();
        $payload = $this->payloadFrom($page, [
            'rooms.0.price' => 16_750,
        ]);

        $this->actingAs($user)
            ->put(route('admin.haji-plus.update'), $payload)
            ->assertRedirect(route('admin.haji-plus.edit'));

        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('$16.750')
            ->assertSee('≈ Rp 276,3 Jt', false);
    }

    public function test_legacy_idr_price_label_is_converted_to_usd_when_rate_set(): void
    {
        \App\Support\HajiExchangeRate::saveManual(16_500, 'USD', true, \App\Support\HajiExchangeRate::MODE_MANUAL);

        HajiPlusPage::save([
            'rooms' => [
                ['key' => 'quad', 'label' => 'Quad', 'occupancy' => '4 orang', 'price_label' => 'Rp 280 Jt', 'price_note' => '/jamaah'],
            ],
        ]);

        $room = HajiPlusPage::content()['rooms'][0] ?? [];
        $this->assertSame(16_970, (int) ($room['price'] ?? 0));
        $this->assertSame('$16.970', $room['price_label'] ?? null);
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
                'image' => $page['hero']['image'],
            ],
            'rooms' => array_map(fn (array $room) => [
                'label' => $room['label'],
                'occupancy' => $room['occupancy'],
                'price' => $room['price'],
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
                'show_names' => ($page['airline']['show_names'] ?? '1') === '1' ? '1' : null,
            ],
            'flow' => $page['flow'],
            'cta' => $page['cta'],
            'detail_program' => $page['detail_program'] ?? HajiPlusPage::defaultDetailProgram(),
        ];

        foreach ($overrides as $path => $value) {
            data_set($payload, $path, $value);
        }

        return $payload;
    }
}
