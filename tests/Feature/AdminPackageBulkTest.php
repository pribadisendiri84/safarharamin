<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPackageBulkTest extends TestCase
{
    use RefreshDatabase;

    private function sourcePackage(): Package
    {
        return Package::query()->create([
            'title' => 'Paket Sumber Import',
            'slug' => 'paket-sumber-import',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'departure_date' => '2026-10-12',
            'duration_days' => 9,
            'price' => 35100000,
            'price_quad' => 35100000,
            'price_triple' => 36200000,
            'price_double' => 38500000,
            'hotel_makkah' => 'Ajyad Makarem',
            'hotel_madinah' => 'Front Taibah',
            'hotel_stars' => 4,
            'airline' => 'Garuda Indonesia',
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'facilities' => ['Tiket PP', 'Hotel'],
            'exclusions' => ['Paspor', 'Vaksin'],
            'price_note' => 'Harga dapat berubah',
            'images' => ['/images/placeholder-kaaba.svg'],
            'status' => 'published',
        ]);
    }

    private function sampleCsvHeader(): string
    {
        return implode(',', [
            'judul', 'jenis', 'tipe_paket', 'embarkasi', 'tanggal', 'durasi',
            'harga_quad', 'harga_triple', 'harga_double',
            'maskapai', 'hotel_makkah', 'hotel_madinah', 'hotel_makkah_setaraf', 'hotel_madinah_setaraf',
            'seat_total', 'seat_sisa',
            'catatan_harga', 'fasilitas', 'exclude', 'status',
        ]);
    }

    private function sampleCsvRow(string $title, string $date): string
    {
        return implode(',', [
            $title,
            'umroh',
            'Arafah',
            'jakarta',
            $date,
            '9',
            '35100000',
            '36200000',
            '38500000',
            'Garuda Indonesia',
            'Hilton',
            'Hilton',
            '1',
            '0',
            '40',
            '40',
            'Harga dapat berubah',
            'Tiket PP|Hotel',
            'Paspor|Vaksin',
            'draft',
        ]);
    }

    public function test_duplicate_opens_prefilled_form_without_creating_package(): void
    {
        $admin = User::factory()->admin()->create();
        $source = $this->sourcePackage();
        $before = Package::query()->count();

        $this->actingAs($admin)
            ->get(route('admin.packages.duplicate', $source))
            ->assertOk()
            ->assertSee('Duplikat paket')
            ->assertSee('Paket Sumber Import (salinan)')
            ->assertSee('Garuda Indonesia')
            ->assertDontSee('Flyer sekarang');

        $this->assertSame($before, Package::query()->count());
    }

    public function test_csv_import_creates_draft_packages_from_csv(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $csv = implode("\n", [
            $this->sampleCsvHeader(),
            $this->sampleCsvRow('Muzdalifah 30 Jan', '2025-01-30'),
            $this->sampleCsvRow('Muzdalifah 15 Feb', '2025-02-15'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.packages.import.store'), [
                'csv' => UploadedFile::fake()->createWithContent('import.csv', $csv),
            ])
            ->assertRedirect(route('admin.packages.index', ['data_complete' => 0]))
            ->assertSessionHas('ok');

        $this->assertDatabaseHas('packages', [
            'title' => 'Muzdalifah 30 Jan',
            'status' => 'draft',
            'airline' => 'Garuda Indonesia',
            'departure_city' => 'jakarta',
            'price_quad' => 35100000,
            'departure_date_display' => 'single',
            'show_seats' => true,
        ]);
        $this->assertDatabaseHas('packages', [
            'title' => 'Muzdalifah 30 Jan',
            'package_kind_id' => $this->packageKindId('arafah'),
            'hotel_makkah' => 'Hilton',
            'hotel_makkah_setaraf' => 1,
            'hotel_madinah_setaraf' => 0,
        ]);
        $this->assertDatabaseHas('packages', ['title' => 'Muzdalifah 15 Feb']);

        $imported = Package::query()->where('title', 'Muzdalifah 30 Jan')->first();
        $this->assertSame([], $imported->images);
        $this->assertSame(['Paspor', 'Vaksin'], $imported->exclusions);
        $this->assertSame(['Tiket PP', 'Hotel'], $imported->facilities);
    }

    public function test_csv_import_supports_optional_catalog_display_columns(): void
    {
        $admin = User::factory()->admin()->create();
        $header = $this->sampleCsvHeader().',tanggal_selesai,tampilan_tanggal,tampilkan_seat';
        $csv = implode("\n", [
            $header,
            $this->sampleCsvRow('Paket Rentang CSV', '2025-03-01').',2025-03-05,range,0',
            $this->sampleCsvRow('Paket Rentang Invalid', '2025-03-10').',2025-03-05,range,1',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.packages.import.store'), [
                'csv' => UploadedFile::fake()->createWithContent('import.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertDatabaseHas('packages', [
            'title' => 'Paket Rentang CSV',
            'departure_date_end' => '2025-03-05 00:00:00',
            'departure_date_display' => 'range',
            'show_seats' => false,
        ]);
        $this->assertDatabaseMissing('packages', ['title' => 'Paket Rentang Invalid']);
    }

    public function test_csv_import_reports_invalid_rows(): void
    {
        $admin = User::factory()->admin()->create();

        $csv = implode("\n", [
            $this->sampleCsvHeader(),
            ',umroh,Arafah,jakarta,2025-01-30,9,35100000,36200000,38500000,GA,Hilton,Hilton,0,0,40,40,,,,draft',
            $this->sampleCsvRow('Valid Row', '2025-03-01'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.packages.import.store'), [
                'csv' => UploadedFile::fake()->createWithContent('import.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertDatabaseHas('packages', ['title' => 'Valid Row']);
        $this->assertDatabaseMissing('packages', ['title' => '']);
    }

    public function test_csv_template_can_be_downloaded(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.packages.import.template'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('judul,jenis,tipe_paket,embarkasi', $response->streamedContent());
    }

    public function test_import_page_has_no_source_package_field(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.packages.import'))
            ->assertOk()
            ->assertSee('Unduh contoh CSV')
            ->assertDontSee('Paket sumber');
    }

    public function test_draft_can_be_saved_without_flyer_but_publish_requires_flyer(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $package = Package::query()->create([
            'title' => 'Draft Tanpa Flyer',
            'slug' => 'draft-tanpa-flyer',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => [],
            'status' => 'draft',
        ]);

        $payload = [
            'title' => 'Draft Tanpa Flyer',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'departure_date' => '2026-12-01',
            'duration_days' => 9,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'package_kind_id' => $this->packageKindId(),
            'seats_total' => 40,
            'seats_left' => 40,
            'status' => 'draft',
        ];

        $this->actingAs($admin)
            ->put(route('admin.packages.update', $package), $payload)
            ->assertRedirect(route('admin.packages.index'));

        $this->actingAs($admin)
            ->from(route('admin.packages.edit', $package))
            ->put(route('admin.packages.update', $package), [...$payload, 'status' => 'published'])
            ->assertRedirect(route('admin.packages.edit', $package))
            ->assertSessionHasErrors('photos');
    }

    public function test_admin_can_upload_preview_and_remove_catalog_cover(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $package = $this->sourcePackage();
        $coverPath = '/storage/packages/covers/paket-sumber-import-abc123.jpg';
        Storage::disk('public')->put('packages/covers/paket-sumber-import-abc123.jpg', 'cover-old');
        $package->update(['cover_image' => $coverPath]);

        $payload = [
            'title' => $package->title,
            'type' => $package->type,
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => $package->departure_city,
            'departure_date' => '2026-10-12',
            'duration_days' => $package->duration_days,
            'price_quad' => $package->price_quad,
            'price_triple' => $package->price_triple,
            'price_double' => $package->price_double,
            'seats_total' => $package->seats_total,
            'seats_left' => $package->seats_left,
            'status' => 'draft',
        ];

        $this->actingAs($admin)
            ->put(route('admin.packages.update', $package), [
                ...$payload,
                'cover_photo' => UploadedFile::fake()->image('cover-baru.jpg', 1200, 600),
            ])
            ->assertRedirect(route('admin.packages.index'));

        $package->refresh();
        $this->assertNotNull($package->cover_image);
        $this->assertNotSame($coverPath, $package->cover_image);
        Storage::disk('public')->assertMissing('packages/covers/paket-sumber-import-abc123.jpg');

        $this->actingAs($admin)
            ->put(route('admin.packages.update', $package), [
                ...$payload,
                'remove_cover' => '1',
            ])
            ->assertRedirect(route('admin.packages.index'));

        $package->refresh();
        $this->assertNull($package->cover_image);
    }

    public function test_admin_can_remove_package_flyer(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $package = $this->sourcePackage();
        $flyerPath = '/storage/packages/flyers/paket-sumber-import-abc123.jpg';
        Storage::disk('public')->put('packages/flyers/paket-sumber-import-abc123.jpg', 'flyer-old');
        $package->update(['images' => [$flyerPath], 'status' => 'draft']);

        $payload = [
            'title' => $package->title,
            'type' => $package->type,
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => $package->departure_city,
            'departure_date' => '2026-10-12',
            'duration_days' => $package->duration_days,
            'price_quad' => $package->price_quad,
            'price_triple' => $package->price_triple,
            'price_double' => $package->price_double,
            'seats_total' => $package->seats_total,
            'seats_left' => $package->seats_left,
            'status' => 'draft',
        ];

        $this->actingAs($admin)
            ->put(route('admin.packages.update', $package), [
                ...$payload,
                'remove_flyer' => '1',
            ])
            ->assertRedirect(route('admin.packages.index'));

        $package->refresh();
        $this->assertSame([], $package->images);
        Storage::disk('public')->assertMissing('packages/flyers/paket-sumber-import-abc123.jpg');
    }

    public function test_admin_package_form_shows_cover_field_with_preview(): void
    {
        $admin = User::factory()->admin()->create();
        $package = $this->sourcePackage();
        $package->update(['cover_image' => '/images/catalog-cover-sample.jpg']);

        $this->actingAs($admin)
            ->get(route('admin.packages.edit', $package))
            ->assertOk()
            ->assertSee('package-media-fieldset', false)
            ->assertSee('Media Paket', false)
            ->assertSee('Cover Katalog', false)
            ->assertSee('Flyer Paket', false)
            ->assertSee('remove_cover', false)
            ->assertSee('remove_flyer', false)
            ->assertSee('/images/catalog-cover-sample.jpg', false);
    }

    public function test_admin_validates_and_saves_package_catalog_display_options(): void
    {
        $admin = User::factory()->admin()->create();
        $package = $this->sourcePackage();
        $payload = [
            'title' => $package->title,
            'type' => $package->type,
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => $package->departure_city,
            'departure_date' => '2026-10-12',
            'departure_date_end' => '2026-10-10',
            'departure_date_display' => 'range',
            'duration_days' => $package->duration_days,
            'price_quad' => $package->price_quad,
            'price_triple' => $package->price_triple,
            'price_double' => $package->price_double,
            'seats_total' => $package->seats_total,
            'seats_left' => $package->seats_left,
            'show_seats' => '0',
            'status' => 'draft',
        ];

        $this->actingAs($admin)
            ->from(route('admin.packages.edit', $package))
            ->put(route('admin.packages.update', $package), $payload)
            ->assertRedirect(route('admin.packages.edit', $package))
            ->assertSessionHasErrors('departure_date_end');

        $this->actingAs($admin)
            ->put(route('admin.packages.update', $package), [
                ...$payload,
                'departure_date_end' => '2026-10-18',
            ])
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'departure_date_end' => '2026-10-18 00:00:00',
            'departure_date_display' => 'range',
            'show_seats' => false,
        ]);
    }

    public function test_data_incomplete_filter_lists_packages_missing_flyer_or_date(): void
    {
        $admin = User::factory()->admin()->create();
        Package::query()->create([
            'title' => 'Paket Data Lengkap',
            'slug' => 'paket-data-lengkap-filter',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'departure_date' => '2026-10-12',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => ['/images/placeholder-kaaba.svg'],
            'status' => 'published',
        ]);
        Package::query()->create([
            'title' => 'Belum Ada Flyer',
            'slug' => 'belum-ada-flyer',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => [],
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.packages.index', ['data_complete' => 0]))
            ->assertOk()
            ->assertSee('Belum Ada Flyer')
            ->assertDontSee('Paket Data Lengkap');

        $this->actingAs($admin)
            ->get(route('admin.packages.index', ['data_complete' => 1]))
            ->assertOk()
            ->assertSee('Paket Data Lengkap')
            ->assertDontSee('Belum Ada Flyer');
    }

    public function test_featured_filter_lists_only_home_packages(): void
    {
        $admin = User::factory()->admin()->create();
        Package::query()->create([
            'title' => 'Paket Beranda',
            'slug' => 'paket-beranda',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'departure_date' => '2026-10-12',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => ['/images/placeholder-kaaba.svg'],
            'status' => 'published',
            'is_featured' => true,
            'home_sort' => 1,
        ]);
        Package::query()->create([
            'title' => 'Paket Non Beranda',
            'slug' => 'paket-non-beranda',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => ['/images/placeholder-kaaba.svg'],
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.packages.index', ['featured' => 1]))
            ->assertOk()
            ->assertSee('Paket Beranda')
            ->assertDontSee('Paket Non Beranda');
    }

    public function test_data_complete_filter_lists_packages_with_flyer_and_departure_date(): void
    {
        $admin = User::factory()->admin()->create();
        Package::query()->create([
            'title' => 'Paket Data Lengkap',
            'slug' => 'paket-data-lengkap',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'departure_date' => '2026-11-01',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => ['/images/placeholder-kaaba.svg'],
            'status' => 'draft',
        ]);
        Package::query()->create([
            'title' => 'Paket Data Belum Lengkap',
            'slug' => 'paket-data-belum-lengkap',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => [],
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.packages.index', ['data_complete' => 1]))
            ->assertOk()
            ->assertSee('Paket Data Lengkap')
            ->assertDontSee('Paket Data Belum Lengkap');

        $this->actingAs($admin)
            ->get(route('admin.packages.index', ['data_complete' => 0]))
            ->assertOk()
            ->assertSee('Paket Data Belum Lengkap')
            ->assertDontSee('Paket Data Lengkap');

        $this->actingAs($admin)
            ->get('/admin/packages?data_complete=&featured=&status=')
            ->assertOk()
            ->assertSee('Paket Data Lengkap')
            ->assertSee('Paket Data Belum Lengkap');
    }

    public function test_admin_can_update_package_status_from_list(): void
    {
        $admin = User::factory()->admin()->create();
        $package = Package::query()->create([
            'title' => 'Paket Ubah Status',
            'slug' => 'paket-ubah-status',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'departure_date' => '2026-10-12',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => ['/images/placeholder-kaaba.svg'],
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->patchJson(route('admin.packages.update-status', $package), ['status' => 'published'])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'published',
            ]);

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'status' => 'published',
        ]);
    }

    public function test_update_status_rejects_publish_without_flyer(): void
    {
        $admin = User::factory()->admin()->create();
        $package = Package::query()->create([
            'title' => 'Paket Tanpa Flyer',
            'slug' => 'paket-tanpa-flyer-status',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'departure_date' => '2026-10-12',
            'duration_days' => 9,
            'price' => 30000000,
            'price_quad' => 30000000,
            'price_triple' => 31100000,
            'price_double' => 33400000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 40,
            'seats_left' => 40,
            'images' => [],
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->patchJson(route('admin.packages.update-status', $package), ['status' => 'published'])
            ->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'status' => 'draft',
            ]);
    }
}
