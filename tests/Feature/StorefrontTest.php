<?php

namespace Tests\Feature;

use App\Models\GalleryItem;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'price_quad' => 29500000,
            'price_triple' => 30600000,
            'price_double' => 32900000,
            'price_note' => 'Harga dapat berubah sesuai kebijakan',
            'exclusions' => ['Paspor', 'Vaksin'],
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 12,
            'status' => 'published',
            'images' => ['/images/placeholder-kaaba.svg'],
            'is_featured' => true,
            'home_sort' => 1,
        ]);
    }

    public function test_home_and_catalog_list_packages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Perjalanan spiritual')
            ->assertSee('Umroh Hemat Contoh')
            ->assertSee('Quad · Triple · Double')
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

    public function test_fullbook_packages_appear_on_catalog_with_badge(): void
    {
        Package::query()->where('slug', 'umroh-hemat-contoh')->update(['status' => 'fullbook']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Umroh Hemat Contoh')
            ->assertSee('Kuota penuh')
            ->assertSee('Terima kasih atas kepercayaan jamaah');

        $this->get('/paket?tipe=umroh')
            ->assertOk()
            ->assertSee('Umroh Hemat Contoh')
            ->assertSee('Kuota penuh');

        $this->get('/paket/umroh-hemat-contoh')
            ->assertOk()
            ->assertSee('Fullbook')
            ->assertSee('Kuota paket ini sudah penuh')
            ->assertDontSee('Tanya / amankan seat');

        $this->post('/paket/umroh-hemat-contoh/tanya', [
            'name' => 'Andi',
            'phone' => '0812111222',
        ])->assertNotFound();
    }

    public function test_package_detail_register_and_inquiry(): void
    {
        $this->get('/paket/umroh-hemat-contoh')
            ->assertOk()
            ->assertSee('Jakarta')
            ->assertSee('Tidak termasuk')
            ->assertSee('Paspor')
            ->assertSee('Mulai Rp')
            ->assertSee('29.500.000')
            ->assertSee('Harga per jamaah')
            ->assertSee('3 org/kamar')
            ->assertSee('Harga dapat berubah sesuai kebijakan');

        $this->get('/galeri')->assertOk()->assertSee('Gallery')->assertSee('gallery-tabs', false);
        $this->get('/galeri?kategori=haji')->assertOk()->assertSee('gallery-tabs', false);
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
        ])->assertRedirect(route('go.whatsapp', ['from' => 'form']));

        $this->assertDatabaseHas('inquiries', ['name' => 'Andi', 'kind' => 'tanya', 'source' => 'website']);
    }

    public function test_admin_can_create_package(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));

        $user = User::factory()->create(['email' => 'admin@safarharamin.id']);

        Storage::fake('public');

        $this->actingAs($user)
            ->post('/admin/packages', [
                'title' => 'Umroh Plus Baru',
                'type' => 'umroh_plus',
                'departure_city' => 'medan',
                'duration_days' => 14,
                'price_quad' => 42000000,
                'price_triple' => 43100000,
                'price_double' => 45400000,
                'hotel_stars' => 4,
                'seats_total' => 30,
                'seats_left' => 30,
                'status' => 'published',
                'photos' => [UploadedFile::fake()->image('flyer.jpg', 400, 560)],
                'exclusions_text' => "Paspor\nVaksin",
                'price_note' => 'Harga dapat berubah sesuai kebijakan',
            ])
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('packages', [
            'title' => 'Umroh Plus Baru',
            'departure_city' => 'medan',
            'price' => 42000000,
            'price_quad' => 42000000,
            'price_triple' => 43100000,
            'price_double' => 45400000,
        ]);
    }

    public function test_admin_cannot_publish_package_without_flyer(): void
    {
        $user = User::factory()->create(['email' => 'admin@safarharamin.id']);

        $this->actingAs($user)
            ->from(route('admin.packages.create'))
            ->post('/admin/packages', [
                'title' => 'Tanpa Flyer',
                'type' => 'umroh',
                'departure_city' => 'jakarta',
                'duration_days' => 9,
                'price_quad' => 35100000,
                'price_triple' => 36200000,
                'price_double' => 38500000,
                'hotel_stars' => 4,
                'seats_total' => 40,
                'seats_left' => 40,
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.packages.create'))
            ->assertSessionHasErrors('photos');

        $this->actingAs($user)
            ->post('/admin/packages', [
                'title' => 'Draft Tanpa Flyer Baru',
                'type' => 'umroh',
                'departure_city' => 'jakarta',
                'duration_days' => 9,
                'price_quad' => 35100000,
                'price_triple' => 36200000,
                'price_double' => 38500000,
                'hotel_stars' => 4,
                'seats_total' => 40,
                'seats_left' => 40,
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('packages', ['title' => 'Draft Tanpa Flyer Baru', 'status' => 'draft']);
    }

    public function test_admin_can_manage_gallery(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/admin/gallery', [
                'title' => 'Manasik Depok',
                'caption' => 'Persiapan jamaah',
                'category' => 'umroh',
                'group_name' => 'Manasik',
                'sort_order' => 1,
                'show_on_home' => '1',
                'image_url' => 'https://images.unsplash.com/photo-1564769625905-50e93615e769?w=800',
            ])
            ->assertRedirect(route('admin.gallery.index'));

        $this->assertDatabaseHas('gallery_items', [
            'title' => 'Manasik Depok',
            'category' => 'umroh',
            'group_name' => 'Manasik',
            'show_on_home' => 1,
        ]);

        $item = GalleryItem::query()->first();

        $this->actingAs($user)
            ->get('/admin/gallery')
            ->assertOk()
            ->assertSee('Manasik Depok');

        $this->actingAs($user)
            ->put('/admin/gallery/'.$item->id, [
                'title' => 'Manasik Jakarta',
                'caption' => 'Updated',
                'category' => 'haji',
                'group_name' => 'Manasik',
                'sort_order' => 2,
                'image_url' => $item->image,
            ])
            ->assertRedirect(route('admin.gallery.index'));

        $this->assertDatabaseHas('gallery_items', [
            'title' => 'Manasik Jakarta',
            'category' => 'haji',
            'group_name' => 'Manasik',
        ]);

        $this->actingAs($user)
            ->patch(route('admin.gallery.toggle-home', $item), ['show_on_home' => '0'])
            ->assertRedirect(route('admin.gallery.index'));

        $this->assertDatabaseHas('gallery_items', [
            'id' => $item->id,
            'show_on_home' => 0,
            'home_sort' => null,
        ]);
    }

    public function test_home_sort_can_be_reordered_by_drag_drop_payload(): void
    {
        $user = User::factory()->create();

        $first = GalleryItem::query()->create([
            'title' => 'Foto 1',
            'image' => 'https://example.com/1.jpg',
            'category' => 'umroh',
            'show_on_home' => true,
            'home_sort' => 1,
        ]);
        $second = GalleryItem::query()->create([
            'title' => 'Foto 2',
            'image' => 'https://example.com/2.jpg',
            'category' => 'umroh',
            'show_on_home' => true,
            'home_sort' => 2,
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.gallery.reorder'), [
                'type' => 'home',
                'order' => [$second->id, $first->id],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('gallery_items', ['id' => $second->id, 'home_sort' => 1]);
        $this->assertDatabaseHas('gallery_items', ['id' => $first->id, 'home_sort' => 2]);
    }

    public function test_gallery_page_sort_can_be_reordered_by_drag_drop_payload(): void
    {
        $user = User::factory()->create();

        $first = GalleryItem::query()->create([
            'title' => 'Foto A',
            'image' => 'https://example.com/a.jpg',
            'category' => 'umroh',
            'sort_order' => 0,
        ]);
        $second = GalleryItem::query()->create([
            'title' => 'Foto B',
            'image' => 'https://example.com/b.jpg',
            'category' => 'umroh',
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.gallery.reorder'), [
                'type' => 'gallery',
                'order' => [$second->id, $first->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('gallery_items', ['id' => $second->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('gallery_items', ['id' => $first->id, 'sort_order' => 1]);
    }

    public function test_gallery_reorder_only_keeps_first_limit_items_on_home(): void
    {
        $user = User::factory()->create();
        $ids = [];

        for ($i = 1; $i <= GalleryItem::homeLimit() + 1; $i++) {
            $ids[] = GalleryItem::query()->create([
                'title' => 'Foto '.$i,
                'image' => 'https://example.com/'.$i.'.jpg',
                'category' => 'umroh',
                'show_on_home' => true,
                'home_sort' => $i,
            ])->id;
        }

        $this->actingAs($user)
            ->postJson(route('admin.gallery.reorder'), [
                'type' => 'home',
                'order' => $ids,
            ])
            ->assertOk();

        $this->assertDatabaseHas('gallery_items', ['id' => $ids[0], 'home_sort' => 1, 'show_on_home' => 1]);
        $this->assertDatabaseHas('gallery_items', ['id' => $ids[GalleryItem::homeLimit()], 'show_on_home' => 0, 'home_sort' => null]);
    }

    public function test_homepage_allows_up_to_nine_displayed_gallery_slots(): void
    {
        for ($slot = 1; $slot <= GalleryItem::homeLimit(); $slot++) {
            GalleryItem::query()->create([
                'title' => 'Beranda '.$slot,
                'image' => 'https://example.com/'.$slot.'.jpg',
                'category' => 'umroh',
                'show_on_home' => true,
                'home_sort' => $slot,
            ]);
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Beranda 1')
            ->assertSee('Beranda '.GalleryItem::homeLimit());

        $this->assertSame(GalleryItem::homeLimit(), GalleryItem::displayedOnHomeCount());
    }

    public function test_package_home_sort_can_be_reordered_by_drag_drop_payload(): void
    {
        $user = User::factory()->create();

        $first = Package::query()->create([
            'title' => 'Paket Beranda 1',
            'slug' => 'paket-beranda-1',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31000000,
            'price_double' => 33000000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 10,
            'status' => 'published',
            'images' => ['/images/placeholder-kaaba.svg'],
            'is_featured' => true,
            'home_sort' => 1,
        ]);
        $second = Package::query()->create([
            'title' => 'Paket Beranda 2',
            'slug' => 'paket-beranda-2',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 12,
            'price' => 35000000,
            'price_quad' => 35000000,
            'price_triple' => 36000000,
            'price_double' => 38000000,
            'hotel_stars' => 5,
            'room_type' => 'quad',
            'seats_total' => 30,
            'seats_left' => 5,
            'status' => 'published',
            'images' => ['/images/placeholder-kaaba.svg'],
            'is_featured' => true,
            'home_sort' => 2,
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.packages.reorder-home'), [
                'order' => [$second->id, $first->id],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('packages', ['id' => $second->id, 'home_sort' => 1]);
        $this->assertDatabaseHas('packages', ['id' => $first->id, 'home_sort' => 2]);
    }

    public function test_package_reorder_only_keeps_first_limit_items_on_home(): void
    {
        $user = User::factory()->create();
        $ids = [];

        for ($i = 1; $i <= Package::homeLimit() + 1; $i++) {
            $ids[] = Package::query()->create([
                'title' => 'Paket Home '.$i,
                'slug' => 'paket-home-'.$i,
                'type' => 'umroh',
                'departure_city' => 'jakarta',
                'duration_days' => 9,
                'price' => 30000000 + ($i * 1000),
                'price_quad' => 30000000 + ($i * 1000),
                'price_triple' => 31000000,
                'price_double' => 33000000,
                'hotel_stars' => 4,
                'room_type' => 'quad',
                'seats_total' => 40,
                'seats_left' => 10,
                'status' => 'published',
                'images' => ['/images/placeholder-kaaba.svg'],
                'is_featured' => true,
                'home_sort' => $i,
            ])->id;
        }

        $this->actingAs($user)
            ->postJson(route('admin.packages.reorder-home'), [
                'order' => $ids,
            ])
            ->assertOk();

        $this->assertDatabaseHas('packages', ['id' => $ids[0], 'home_sort' => 1, 'is_featured' => 1]);
        $this->assertDatabaseHas('packages', ['id' => $ids[Package::homeLimit() - 1], 'home_sort' => Package::homeLimit(), 'is_featured' => 1]);
        $this->assertDatabaseHas('packages', ['id' => $ids[Package::homeLimit()], 'is_featured' => 0, 'home_sort' => null]);
    }

    public function test_homepage_allows_up_to_eight_displayed_package_slots(): void
    {
        Package::query()->where('slug', 'umroh-hemat-contoh')->update([
            'is_featured' => false,
            'home_sort' => null,
        ]);

        for ($slot = 1; $slot <= Package::homeLimit(); $slot++) {
            Package::query()->create([
                'title' => 'Beranda Paket '.$slot,
                'slug' => 'beranda-paket-'.$slot,
                'type' => 'umroh',
                'departure_city' => 'jakarta',
                'duration_days' => 9,
                'price' => 30000000,
                'price_quad' => 30000000,
                'price_triple' => 31000000,
                'price_double' => 33000000,
                'hotel_stars' => 4,
                'room_type' => 'quad',
                'seats_total' => 40,
                'seats_left' => 10,
                'status' => 'published',
                'images' => ['/images/placeholder-kaaba.svg'],
                'is_featured' => true,
                'home_sort' => $slot,
            ]);
        }

        $response = $this->get('/')->assertOk();

        for ($slot = 1; $slot <= Package::homeLimit(); $slot++) {
            $response->assertSee('Beranda Paket '.$slot);
        }

        $this->assertSame(Package::homeLimit(), Package::query()->displayedOnHome()->count());
    }

    public function test_admin_can_toggle_package_featured_from_list(): void
    {
        $user = User::factory()->create();
        $package = Package::query()->where('slug', 'umroh-hemat-contoh')->firstOrFail();

        $this->actingAs($user)
            ->patch(route('admin.packages.toggle-featured', $package), ['is_featured' => '0'])
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'is_featured' => 0,
            'home_sort' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.packages.toggle-featured', $package), ['is_featured' => '1'])
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'is_featured' => 1,
            'home_sort' => 1,
        ]);
    }

    public function test_toggling_featured_when_home_slots_full_goes_to_reserve(): void
    {
        $user = User::factory()->create();

        Package::query()->where('slug', 'umroh-hemat-contoh')->update([
            'is_featured' => false,
            'home_sort' => null,
        ]);

        for ($slot = 1; $slot <= Package::homeLimit(); $slot++) {
            Package::query()->create([
                'title' => 'Slot Paket '.$slot,
                'slug' => 'slot-paket-'.$slot,
                'type' => 'umroh',
                'departure_city' => 'jakarta',
                'duration_days' => 9,
                'price' => 30000000,
                'price_quad' => 30000000,
                'price_triple' => 31000000,
                'price_double' => 33000000,
                'hotel_stars' => 4,
                'room_type' => 'quad',
                'seats_total' => 40,
                'seats_left' => 10,
                'status' => 'published',
                'images' => ['/images/placeholder-kaaba.svg'],
                'is_featured' => true,
                'home_sort' => $slot,
            ]);
        }

        $extra = Package::query()->create([
            'title' => 'Paket Cadangan',
            'slug' => 'paket-cadangan',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 28000000,
            'price_quad' => 28000000,
            'price_triple' => 29000000,
            'price_double' => 31000000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 10,
            'status' => 'published',
            'images' => ['/images/placeholder-kaaba.svg'],
            'is_featured' => false,
        ]);

        $this->actingAs($user)
            ->patchJson(route('admin.packages.toggle-featured', $extra), ['is_featured' => '1'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertDatabaseHas('packages', [
            'id' => $extra->id,
            'is_featured' => 0,
            'home_sort' => null,
        ]);
    }

    public function test_gallery_toggle_rejects_when_home_limit_reached(): void
    {
        $user = User::factory()->create();

        for ($slot = 1; $slot <= GalleryItem::homeLimit(); $slot++) {
            GalleryItem::query()->create([
                'title' => 'Galeri Slot '.$slot,
                'image' => 'https://example.com/'.$slot.'.jpg',
                'category' => 'umroh',
                'show_on_home' => true,
                'home_sort' => $slot,
            ]);
        }

        $extra = GalleryItem::query()->create([
            'title' => 'Galeri Extra',
            'image' => 'https://example.com/extra.jpg',
            'category' => 'umroh',
            'show_on_home' => false,
        ]);

        $this->actingAs($user)
            ->patchJson(route('admin.gallery.toggle-home', $extra), ['show_on_home' => '1'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertDatabaseHas('gallery_items', [
            'id' => $extra->id,
            'show_on_home' => 0,
            'home_sort' => null,
        ]);
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
