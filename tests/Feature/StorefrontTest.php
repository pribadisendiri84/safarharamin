<?php

namespace Tests\Feature;

use App\Models\GalleryItem;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('wa_number', '6281234567890');

        Package::query()->create([
            'title' => 'Umroh Hemat Contoh',
            'slug' => 'umroh-hemat-contoh',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'departure_date' => '2026-10-12',
            'duration_days' => 9,
            'price' => 29500000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 12,
            'status' => 'published',
        ]);
    }

    public function test_home_and_catalog_list_packages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Perjalanan spiritual')
            ->assertSee('Umroh Hemat Contoh')
            ->assertSee('Jakarta')
            ->assertDontSee('98%');

        $this->get('/paket?tipe=umroh')
            ->assertOk()
            ->assertSee('Umroh Reguler')
            ->assertSee('Umroh Hemat Contoh');

        $this->get('/paket?kelompok=umroh')
            ->assertOk()
            ->assertSee('Paket umroh')
            ->assertSee('Umroh Hemat Contoh');

        $this->get('/paket?kota=jakarta')
            ->assertOk()
            ->assertSee('Umroh Hemat Contoh');

        $this->get('/paket?tipe=haji_plus')
            ->assertOk()
            ->assertDontSee('Umroh Hemat Contoh');
    }

    public function test_package_detail_register_and_inquiry(): void
    {
        $this->get('/paket/umroh-hemat-contoh')
            ->assertOk()
            ->assertSee('Jakarta');

        $this->get('/galeri')->assertOk()->assertSee('Gallery');
        $this->get('/testimoni')->assertOk()->assertSee('Testimoni');
        $this->get('/haji-plus')->assertOk();
        $this->get('/tabungan')->assertNotFound();
        $this->get('/kalkulator-cicilan')->assertNotFound();

        $this->post('/daftar', [
            'name' => 'Budi',
            'phone' => '08123456789',
            'city' => 'jakarta',
            'pax' => 2,
        ])->assertRedirect(route('register'));

        $this->assertDatabaseHas('inquiries', ['name' => 'Budi', 'kind' => 'daftar', 'source' => 'website']);

        $this->post('/paket/umroh-hemat-contoh/tanya', [
            'name' => 'Andi',
            'phone' => '0812111222',
            'pax' => 1,
        ])->assertRedirect(route('packages.show', 'umroh-hemat-contoh'));

        $this->assertDatabaseHas('inquiries', ['name' => 'Andi', 'kind' => 'tanya', 'source' => 'website']);
    }

    public function test_admin_can_create_package(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));

        $user = User::factory()->create(['email' => 'admin@safarharamin.id']);

        $this->actingAs($user)
            ->post('/admin/packages', [
                'title' => 'Umroh Plus Baru',
                'type' => 'umroh_plus',
                'departure_city' => 'medan',
                'duration_days' => 14,
                'price' => 42000000,
                'hotel_stars' => 4,
                'room_type' => 'quad',
                'seats_total' => 30,
                'seats_left' => 30,
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('packages', ['title' => 'Umroh Plus Baru', 'departure_city' => 'medan']);
    }

    public function test_admin_can_manage_gallery(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/admin/gallery', [
                'title' => 'Manasik Depok',
                'caption' => 'Persiapan jamaah',
                'sort_order' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1564769625905-50e93615e769?w=800',
            ])
            ->assertRedirect(route('admin.gallery.index'));

        $this->assertDatabaseHas('gallery_items', ['title' => 'Manasik Depok']);

        $item = GalleryItem::query()->first();

        $this->actingAs($user)
            ->get('/admin/gallery')
            ->assertOk()
            ->assertSee('Manasik Depok');

        $this->actingAs($user)
            ->put('/admin/gallery/'.$item->id, [
                'title' => 'Manasik Jakarta',
                'caption' => 'Updated',
                'sort_order' => 2,
                'image_url' => $item->image,
            ])
            ->assertRedirect(route('admin.gallery.index'));

        $this->assertDatabaseHas('gallery_items', ['title' => 'Manasik Jakarta']);
    }

    public function test_admin_can_manage_testimonials(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/admin/testimonials', [
                'name' => 'Ibu Rina',
                'city' => 'Bogor',
                'package_title' => 'Umroh Hemat 9 Hari Jakarta',
                'quote' => 'Pembimbing sabar dan hotel dekat masjid.',
                'sort_order' => 1,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.testimonials.index'));

        $this->assertDatabaseHas('testimonials', ['name' => 'Ibu Rina', 'is_published' => 1]);

        $this->get('/testimoni')->assertOk()->assertSee('Ibu Rina');

        $item = Testimonial::query()->first();

        $this->actingAs($user)
            ->put('/admin/testimonials/'.$item->id, [
                'name' => 'Ibu Rina',
                'city' => 'Depok',
                'quote' => 'Sangat terbantu.',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.testimonials.index'));

        $this->assertDatabaseHas('testimonials', ['city' => 'Depok', 'is_published' => 0]);
    }
}
