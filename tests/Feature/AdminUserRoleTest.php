<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_create_and_list_users(): void
    {
        $super = User::factory()->superadmin()->create();

        $this->actingAs($super)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Tambah pengguna');

        $this->actingAs($super)
            ->post('/admin/users', [
                'name' => 'Staf Paket',
                'email' => 'staf@safarharamin.id',
                'password' => 'password123',
                'role' => UserRole::Admin->value,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'staf@safarharamin.id',
            'role' => UserRole::Admin->value,
        ]);
    }

    public function test_admin_can_manage_catalog_but_cannot_create_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Riwayat');

        $this->actingAs($admin)
            ->get('/admin/riwayat')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Tidak Boleh',
                'email' => 'nope@safarharamin.id',
                'password' => 'password123',
                'role' => UserRole::Admin->value,
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('users', ['email' => 'nope@safarharamin.id']);
    }

    public function test_superadmin_cannot_delete_self_or_last_superadmin(): void
    {
        $super = User::factory()->superadmin()->create();

        $this->actingAs($super)
            ->delete('/admin/users/'.$super->id)
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $super->id]);

        $this->actingAs($super)
            ->put('/admin/users/'.$super->id, [
                'name' => $super->name,
                'email' => $super->email,
                'role' => UserRole::Admin->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $super->id,
            'role' => UserRole::Superadmin->value,
        ]);
    }

    public function test_staff_can_only_access_inquiries(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/admin')->assertOk();
        $this->actingAs($staff)->get('/admin/inquiries')->assertOk();
        $this->actingAs($staff)->get('/admin/inquiries/create')->assertOk();
        $this->actingAs($staff)->get('/admin/packages')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($staff)->get('/admin/cities')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($staff)->get('/admin/users')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($staff)->get('/admin/trafik')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($staff)->get('/admin/riwayat')->assertRedirect(route('admin.dashboard'));
    }
}
