<?php

namespace Tests\Feature;

use App\Models\Departure;
use App\Models\Pic;
use App\Models\Pilgrim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPicTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_update_and_soft_delete_pic(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.pics.index'))
            ->assertOk()
            ->assertSee('Master PIC')
            ->assertSee('Yanti');

        $this->actingAs($admin)
            ->post(route('admin.pics.store'), [
                'name' => 'Rina PIC',
                'phone' => '081234567890',
                'sort_order' => 40,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.pics.index'));

        $this->assertDatabaseHas('pics', [
            'name' => 'Rina PIC',
            'phone' => '081234567890',
            'is_active' => 1,
        ]);

        $pic = Pic::query()->where('name', 'Rina PIC')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.pics.update', $pic), [
                'name' => 'Rina PIC',
                'phone' => '081200000000',
                'sort_order' => 5,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.pics.index'));

        $this->assertDatabaseHas('pics', [
            'id' => $pic->id,
            'phone' => '081200000000',
            'sort_order' => 5,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.pics.destroy', $pic))
            ->assertRedirect(route('admin.pics.index'));

        $this->assertSoftDeleted('pics', ['id' => $pic->id]);
    }

    public function test_pic_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $pic = Pic::query()->where('name', 'Yanti')->firstOrFail();
        $departure = Departure::query()->create([
            'program_name' => 'Umroh PIC Terpakai',
            'program_kind' => 'umroh',
        ]);

        Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'pic_id' => $pic->id,
            'full_name' => 'Jamaah PIC Terpakai',
            'room_type' => 'quad',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.pics.destroy', $pic))
            ->assertSessionHasErrors();

        $this->assertNotSoftDeleted('pics', ['id' => $pic->id]);
    }

    public function test_staff_cannot_manage_pic_master(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('admin.pics.index'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
