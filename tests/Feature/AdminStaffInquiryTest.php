<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_team_inquiry_with_pic(): void
    {
        $staff = User::factory()->staff()->create(['name' => 'Yanti']);
        $package = $this->makePackage();

        $this->actingAs($staff)
            ->post(route('admin.inquiries.store'), [
                'kind' => 'daftar',
                'name' => 'Ibu Sari',
                'phone' => '0812999000',
                'city' => 'jakarta',
                'package_id' => $package->id,
                'pax' => 2,
                'pic_id' => $staff->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Ibu Sari',
            'source' => Inquiry::SOURCE_TEAM,
            'pic_id' => $staff->id,
            'kind' => 'daftar',
        ]);

        $inquiry = Inquiry::query()->where('name', 'Ibu Sari')->first();
        $this->actingAs($staff)
            ->put(route('admin.inquiries.update', $inquiry), [
                'status' => Inquiry::STATUS_SOLD,
                'package_id' => $package->id,
                'sold_pax' => 2,
                'pic_id' => $staff->id,
            ])
            ->assertRedirect();

        $this->assertTrue($inquiry->fresh()->isSold());
    }

    public function test_staff_cannot_delete_inquiry(): void
    {
        $staff = User::factory()->staff()->create();
        $inquiry = Inquiry::query()->create([
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_TEAM,
            'name' => 'Pak Budi',
            'phone' => '0812111',
            'status' => Inquiry::STATUS_NEW,
            'pic_id' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->from(route('admin.inquiries.index'))
            ->delete(route('admin.inquiries.destroy', $inquiry))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertNotSoftDeleted('inquiries', ['id' => $inquiry->id]);
    }

    public function test_superadmin_dashboard_splits_website_and_team_funnel(): void
    {
        $super = User::factory()->superadmin()->create(['name' => 'Owner']);
        $staff = User::factory()->staff()->create(['name' => 'Yanti']);
        $package = $this->makePackage();

        Inquiry::query()->create([
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_WEBSITE,
            'name' => 'Daftar Web',
            'phone' => '0812001',
            'package_id' => $package->id,
            'status' => Inquiry::STATUS_SOLD,
            'sold_pax' => 1,
            'sold_amount' => 29500000,
            'pic_id' => $staff->id,
        ]);
        Inquiry::query()->create([
            'kind' => 'tanya',
            'source' => Inquiry::SOURCE_WEBSITE,
            'name' => 'Tanya Web',
            'phone' => '0812002',
            'status' => Inquiry::STATUS_NEW,
        ]);
        Inquiry::query()->create([
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_TEAM,
            'name' => 'Input Tim',
            'phone' => '0812003',
            'status' => Inquiry::STATUS_SOLD,
            'sold_pax' => 2,
            'sold_amount' => 59000000,
            'pic_id' => $staff->id,
        ]);

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Daftar lewat website')
            ->assertSee('Tanya WA lewat website')
            ->assertSee('Input tim')
            ->assertSee('PIC Yanti');

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Funnel website vs tim')
            ->assertDontSee('Tanya Web')
            ->assertSee('Daftar Web')
            ->assertSee('Input Tim')
            ->assertDontSee('Website')
            ->assertDontSee('Input tim');
    }

    public function test_staff_only_see_their_own_inquiries_and_not_the_source(): void
    {
        $staff = User::factory()->staff()->create();
        $other = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $mine = Inquiry::query()->create([
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_WEBSITE,
            'name' => 'Jamaah Saya',
            'phone' => '0812111001',
            'status' => Inquiry::STATUS_NEW,
            'pic_id' => $staff->id,
        ]);
        $teammate = Inquiry::query()->create([
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_TEAM,
            'name' => 'Jamaah Teman',
            'phone' => '0812111002',
            'status' => Inquiry::STATUS_NEW,
            'pic_id' => $other->id,
        ]);
        $unassigned = Inquiry::query()->create([
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_WEBSITE,
            'name' => 'Jamaah Website',
            'phone' => '0812111003',
            'status' => Inquiry::STATUS_NEW,
        ]);

        $this->actingAs($staff)
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertSee('Jamaah Saya')
            ->assertDontSee('Jamaah Teman')
            ->assertDontSee('Jamaah Website')
            ->assertDontSee('Website')
            ->assertDontSee('Semua sumber')
            ->assertDontSee('Input tim');

        $this->actingAs($staff)
            ->get(route('admin.inquiries.show', $mine))
            ->assertOk()
            ->assertSee('Jamaah Saya')
            ->assertDontSee('Website');

        $this->actingAs($staff)
            ->get(route('admin.inquiries.show', $teammate))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($staff)
            ->get(route('admin.inquiries.show', $unassigned))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertSee('Jamaah Saya')
            ->assertSee('Jamaah Teman')
            ->assertSee('Jamaah Website')
            ->assertSee('Website');
    }

    private function makePackage(): Package
    {
        return Package::query()->create([
            'title' => 'Umroh PIC',
            'slug' => 'umroh-pic',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 29500000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 20,
            'seats_left' => 20,
            'status' => 'published',
        ]);
    }
}
