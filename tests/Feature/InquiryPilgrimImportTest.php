<?php

namespace Tests\Feature;

use App\Models\Departure;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Pic;
use App\Models\Pilgrim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryPilgrimImportTest extends TestCase
{
    use RefreshDatabase;

    private function makePackage(array $overrides = []): Package
    {
        return Package::query()->create(array_merge([
            'title' => 'Umroh Import Test',
            'slug' => 'umroh-import-test-'.uniqid(),
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 29_500_000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 20,
            'seats_left' => 20,
            'status' => 'published',
        ], $overrides));
    }

    public function test_closing_inquiry_can_be_imported_to_pilgrims(): void
    {
        $admin = User::factory()->admin()->create();
        $package = $this->makePackage();
        $departure = Departure::query()->create([
            'package_id' => $package->id,
            'program_name' => 'Umroh April 2026',
            'program_kind' => 'umroh',
            'departure_date' => '2026-04-12',
        ]);

        $inquiry = Inquiry::query()->create([
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_TEAM,
            'name' => 'Abdul Hadi',
            'phone' => '081250344023',
            'pax' => 2,
            'package_id' => $package->id,
            'pic_id' => $admin->id,
            'status' => Inquiry::STATUS_SOLD,
            'sold_pax' => 2,
            'sold_amount' => 59_000_000,
            'closed_at' => now(),
            'seats_applied' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.import-pilgrims', $inquiry), [
                'departure_id' => $departure->id,
                'room_type' => 'quad',
                'names' => ['Abdul Hadi', 'Nurhayati'],
            ])
            ->assertRedirect(route('admin.operations.pilgrims.index', ['departure_id' => $departure->id]))
            ->assertSessionHas('ok');

        $inquiry->refresh();
        $this->assertTrue($inquiry->pilgrimsImported());
        $this->assertSame('selesai', $inquiry->status);

        $pilgrims = Pilgrim::query()->where('inquiry_id', $inquiry->id)->orderBy('id')->get();
        $this->assertCount(2, $pilgrims);
        $this->assertSame('Abdul Hadi', $pilgrims[0]->full_name);
        $this->assertSame('081250344023', $pilgrims[0]->phone);
        $this->assertSame(29_500_000, (int) $pilgrims[0]->package_price);
        $this->assertSame('Nurhayati', $pilgrims[1]->full_name);
        $this->assertNull($pilgrims[1]->phone);
        $pic = Pic::query()->whereRaw('lower(name) = ?', [mb_strtolower($admin->name)])->first();
        $this->assertNotNull($pic);
        $this->assertSame($pic->id, $pilgrims[0]->pic_id);
        $this->assertSame($pic->id, $pilgrims[1]->pic_id);
    }

    public function test_cannot_import_inquiry_twice(): void
    {
        $admin = User::factory()->admin()->create();
        $package = $this->makePackage();
        $departure = Departure::query()->create([
            'package_id' => $package->id,
            'program_name' => 'Umroh Test',
            'program_kind' => 'umroh',
        ]);

        $inquiry = Inquiry::query()->create([
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_TEAM,
            'name' => 'Budi',
            'phone' => '081234567890',
            'pax' => 1,
            'package_id' => $package->id,
            'pic_id' => $admin->id,
            'status' => Inquiry::STATUS_SOLD,
            'sold_pax' => 1,
            'sold_amount' => 29_500_000,
            'closed_at' => now(),
            'pilgrims_imported_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.import-pilgrims', $inquiry), [
                'departure_id' => $departure->id,
                'room_type' => 'quad',
                'names' => ['Budi'],
            ])
            ->assertRedirect();

        $this->assertSame(0, Pilgrim::query()->where('inquiry_id', $inquiry->id)->count());
    }

    public function test_inquiry_show_displays_import_form_for_closing(): void
    {
        $admin = User::factory()->admin()->create();
        $package = $this->makePackage();
        Departure::query()->create([
            'package_id' => $package->id,
            'program_name' => 'Umroh April 2026',
            'program_kind' => 'umroh',
        ]);

        $inquiry = Inquiry::query()->create([
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_TEAM,
            'name' => 'Siti',
            'phone' => '081111111111',
            'pax' => 1,
            'package_id' => $package->id,
            'pic_id' => $admin->id,
            'status' => Inquiry::STATUS_SOLD,
            'sold_pax' => 1,
            'sold_amount' => 29_500_000,
            'closed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee('Pindah ke Jamaah')
            ->assertSee('Umroh April 2026');
    }
}
