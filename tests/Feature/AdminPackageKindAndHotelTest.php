<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Package;
use App\Models\PackageKind;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPackageKindAndHotelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_package_kinds(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.package-kinds.index'))
            ->assertOk()
            ->assertSee('Master Tipe Paket')
            ->assertSee('Arafah')
            ->assertSee('Mina')
            ->assertSee('Muzdalifah');

        $this->actingAs($admin)
            ->post(route('admin.package-kinds.store'), [
                'name' => 'Quba',
                'sort_order' => 40,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.package-kinds.index'));

        $this->assertDatabaseHas('package_kinds', ['name' => 'Quba', 'slug' => 'quba', 'is_active' => 1]);

        $kind = PackageKind::query()->where('slug', 'quba')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.package-kinds.update', $kind), [
                'name' => 'Quba',
                'sort_order' => 5,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.package-kinds.index'));

        $this->assertDatabaseHas('package_kinds', ['id' => $kind->id, 'sort_order' => 5]);

        $this->actingAs($admin)
            ->delete(route('admin.package-kinds.destroy', $kind))
            ->assertRedirect(route('admin.package-kinds.index'));

        $this->assertSoftDeleted('package_kinds', ['id' => $kind->id]);
    }

    public function test_package_kind_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $kind = PackageKind::query()->where('slug', 'arafah')->firstOrFail();

        Package::query()->create([
            'title' => 'Paket Pakai Arafah',
            'slug' => 'paket-pakai-arafah',
            'type' => 'umroh',
            'package_kind_id' => $kind->id,
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 30000000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 20,
            'seats_left' => 10,
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.package-kinds.index'))
            ->delete(route('admin.package-kinds.destroy', $kind))
            ->assertRedirect(route('admin.package-kinds.index'))
            ->assertSessionHasErrors();

        $this->assertNotSoftDeleted('package_kinds', ['id' => $kind->id]);
    }

    public function test_hilton_can_exist_in_makkah_and_madinah_but_not_twice_in_one_city(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.hotels.store'), [
                'name' => 'Grand Unique',
                'location' => Hotel::LOCATION_MAKKAH,
                'stars' => 5,
                'sort_order' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.hotels.store'), [
                'name' => 'Grand Unique',
                'location' => Hotel::LOCATION_MADINAH,
                'stars' => 4,
                'sort_order' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('hotels', ['name' => 'Grand Unique', 'location' => Hotel::LOCATION_MAKKAH, 'stars' => 5]);
        $this->assertDatabaseHas('hotels', ['name' => 'Grand Unique', 'location' => Hotel::LOCATION_MADINAH, 'stars' => 4]);

        $this->actingAs($admin)
            ->from(route('admin.hotels.index'))
            ->post(route('admin.hotels.store'), [
                'name' => 'Grand Unique',
                'location' => Hotel::LOCATION_MAKKAH,
                'stars' => 3,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.hotels.index'))
            ->assertSessionHasErrors('name');
    }

    public function test_hotel_requires_stars(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.hotels.index'))
            ->post(route('admin.hotels.store'), [
                'name' => 'Tanpa Bintang',
                'location' => Hotel::LOCATION_MAKKAH,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.hotels.index'))
            ->assertSessionHasErrors('stars');

        $this->actingAs($admin)
            ->get(route('admin.hotels.index'))
            ->assertOk()
            ->assertSee('Hilton')
            ->assertSee('name="stars"', false);
    }
}
