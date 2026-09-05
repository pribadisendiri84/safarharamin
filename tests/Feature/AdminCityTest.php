<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCityTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_embarkation_cities_are_seeded(): void
    {
        $this->assertDatabaseHas('cities', ['slug' => 'jakarta', 'name' => 'Jakarta']);
        $this->assertDatabaseHas('cities', ['slug' => 'jakarta-pusat', 'name' => 'Jakarta Pusat']);
        $this->assertDatabaseHas('cities', ['slug' => 'jakarta-timur', 'name' => 'Jakarta Timur']);
        $this->assertDatabaseHas('cities', ['slug' => 'padang', 'name' => 'Padang']);
        $this->assertDatabaseHas('cities', ['slug' => 'banda-aceh', 'name' => 'Banda Aceh']);
        $this->assertDatabaseHas('cities', ['slug' => 'cilegon', 'name' => 'Cilegon']);
        $this->assertCount(99, City::query()->get());

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.cities.index'))
            ->assertOk()
            ->assertSee('99 kota')
            ->assertSee('Jakarta Timur')
            ->assertSee('Yogyakarta')
            ->assertSee('Tidore Kepulauan');
    }

    public function test_admin_can_add_and_use_a_city(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.cities.store'), [
                'name' => 'Kota Uji',
                'sort_order' => 200,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.cities.index'));

        $this->assertDatabaseHas('cities', ['slug' => 'kota-uji', 'name' => 'Kota Uji']);

        Storage::fake('public');

        $this->actingAs($admin)
            ->post('/admin/packages', [
                'title' => 'Umroh Kota Uji',
                'type' => 'umroh',
                'departure_city' => 'kota-uji',
                'duration_days' => 9,
                'price_quad' => 30000000,
                'price_triple' => 31100000,
                'price_double' => 33400000,
                'package_kind_id' => $this->packageKindId(),
                'seats_total' => 20,
                'seats_left' => 20,
                'status' => 'published',
                'photos' => [UploadedFile::fake()->image('flyer.jpg', 400, 560)],
            ])
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('packages', ['title' => 'Umroh Kota Uji', 'departure_city' => 'kota-uji']);

        $this->get('/daftar')->assertOk()->assertSee('Kota Uji');
        $this->get('/admin/packages/create')
            ->assertOk()
            ->assertSee('js-searchable')
            ->assertDontSee('URL flyer');
    }

    public function test_city_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $city = City::query()->where('slug', 'jakarta')->firstOrFail();

        Package::query()->create([
            'title' => 'Umroh Jakarta',
            'slug' => 'umroh-jakarta',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 30000000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 20,
            'seats_left' => 20,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.cities.index'))
            ->delete(route('admin.cities.destroy', $city))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertNotSoftDeleted('cities', ['id' => $city->id]);
    }
}
