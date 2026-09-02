<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminHistoryAndSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleted_package_is_hidden_from_storefront_and_can_be_restored(): void
    {
        $admin = User::factory()->admin()->create();
        $package = Package::query()->create([
            'title' => 'Umroh Soft Delete',
            'slug' => 'umroh-soft-delete',
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
            ->delete('/admin/packages/'.$package->id)
            ->assertRedirect(route('admin.packages.index'));

        $this->assertSoftDeleted('packages', ['id' => $package->id]);

        $this->get('/paket/umroh-soft-delete')->assertNotFound();
        $this->get('/paket')->assertDontSee('Umroh Soft Delete');

        $this->actingAs($admin)
            ->get('/admin/packages?trashed=1')
            ->assertOk()
            ->assertSee('Umroh Soft Delete')
            ->assertSee('Pulihkan');

        $this->actingAs($admin)
            ->post('/admin/packages/'.$package->id.'/restore')
            ->assertRedirect();

        $this->assertNotSoftDeleted('packages', ['id' => $package->id]);
        $this->get('/paket/umroh-soft-delete')->assertOk()->assertSee('Umroh Soft Delete');
    }

    public function test_admin_actions_are_written_to_history(): void
    {
        $admin = User::factory()->admin()->create();

        Storage::fake('public');

        $this->actingAs($admin)
            ->post('/admin/packages', [
                'title' => 'Paket Riwayat',
                'type' => 'umroh',
                'departure_city' => 'medan',
                'duration_days' => 10,
                'price_quad' => 35000000,
                'price_triple' => 36100000,
                'price_double' => 38400000,
                'hotel_stars' => 4,
                'seats_total' => 25,
                'seats_left' => 25,
                'status' => 'published',
                'photos' => [UploadedFile::fake()->image('flyer.jpg', 400, 560)],
            ])
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'subject_type' => Package::class,
            'subject_label' => 'Paket Riwayat',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get('/admin/riwayat')
            ->assertRedirect(route('admin.dashboard'));

        $super = User::factory()->superadmin()->create();

        $this->actingAs($super)
            ->get('/admin/riwayat')
            ->assertOk()
            ->assertSee('Paket Riwayat')
            ->assertSee('Dibuat');

        $this->actingAs($super)
            ->get('/admin')
            ->assertSee('Riwayat');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertDontSee('Riwayat');
    }

    public function test_deleted_user_cannot_login_until_restored(): void
    {
        $super = User::factory()->superadmin()->create();
        $admin = User::factory()->admin()->create([
            'email' => 'staf@safarharamin.id',
            'password' => 'password123',
        ]);

        $this->actingAs($super)
            ->delete('/admin/users/'.$admin->id)
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $admin->id]);

        $this->post('/admin/logout');

        $this->post('/admin/login', [
            'email' => 'staf@safarharamin.id',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->actingAs($super)
            ->post('/admin/users/'.$admin->id.'/restore')
            ->assertRedirect();

        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }
}
