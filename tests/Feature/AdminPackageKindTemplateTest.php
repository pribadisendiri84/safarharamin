<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageKind;
use App\Models\PriceSyncChange;
use App\Models\PriceSyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPackageKindTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_admin_can_save_package_kind_content_template(): void
    {
        $admin = User::factory()->admin()->create();
        $kind = PackageKind::query()->where('slug', 'muzdalifah')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.package-kinds.template.update', $kind), [
                'description' => 'Paket Muzdalifah dengan hotel bintang 5.',
                'hotel_makkah' => 'Hilton Suites Makkah',
                'hotel_makkah_setaraf' => '1',
                'hotel_madinah' => 'Front Taibah',
                'facilities_text' => "Tiket PP\nVisa",
                'exclusions_text' => "Paspor\nVaksin",
            ])
            ->assertRedirect(route('admin.package-kinds.template.edit', $kind));

        $kind->refresh();
        $this->assertSame('Paket Muzdalifah dengan hotel bintang 5.', $kind->description);
        $this->assertSame(['Tiket PP', 'Visa'], $kind->facilities);
        $this->assertSame(['Paspor', 'Vaksin'], $kind->exclusions);
        $this->assertTrue($kind->hasContentTemplate());
    }

    #[Test]
    public function test_admin_can_upload_default_catalog_cover_on_kind_template(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $kind = PackageKind::query()->where('slug', 'arafah')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.package-kinds.template.update', $kind), [
                'cover_photo' => UploadedFile::fake()->image('arafah-cover.jpg', 1200, 600),
            ])
            ->assertRedirect(route('admin.package-kinds.template.edit', $kind));

        $kind->refresh();
        $this->assertNotNull($kind->cover_image);
        $this->assertStringStartsWith('/storage/packages/covers/', $kind->cover_image);
        $this->assertTrue($kind->hasContentTemplate());
    }

    #[Test]
    public function test_sync_new_product_applies_kind_template_when_content_missing(): void
    {
        config([
            'arminareka.jadwal.request_delay_ms' => 0,
            'arminareka.jadwal.page_size' => 15,
            'arminareka.jadwal.max_pages' => 2,
        ]);

        $kind = PackageKind::query()->where('slug', 'muzdalifah')->firstOrFail();
        $kind->update([
            'description' => 'Paket {type_short} {package_kind} {duration_days} Hari dengan hotel bintang {hotel_stars}, maskapai {airline}, dan pendampingan muthawwif berbahasa Indonesia.',
            'hotel_makkah' => 'Hilton',
            'facilities' => ['Tiket PP', 'Visa'],
            'exclusions' => ['Paspor'],
            'cover_image' => '/images/catalog-cover-sample.svg',
        ]);

        $html = <<<'HTML'
<table><tbody>
<tr data-key="7001"><td>1</td><td>Muzdalifah JT CGK 12D JED (07/12/2026)</td><td>7 Dec 2026</td><td>38.800.000</td><td>40.300.000</td><td>43.400.000</td><td>45</td><td>45</td></tr>
</tbody></table>
HTML;

        Http::fake(['*' => Http::response($html, 200)]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.price-sync.store'))
            ->assertRedirect();

        $run = PriceSyncRun::query()->firstOrFail();
        $change = $run->changes()->where('change_status', PriceSyncChange::STATUS_NEW)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.price-sync.apply', $run), [
                'change_ids' => [$change->id],
            ])
            ->assertRedirect(route('admin.price-sync.show', $run));

        $package = Package::query()->where('source_key', 'arminareka:7001')->firstOrFail();
        $this->assertSame(
            'Paket Umroh Muzdalifah 12 Hari dengan hotel bintang 5, maskapai Lion Air, dan pendampingan muthawwif berbahasa Indonesia.',
            $package->description,
        );
        $this->assertSame('Hilton', $package->hotel_makkah);
        $this->assertSame(['Tiket PP', 'Visa'], $package->facilities);
        $this->assertSame(['Paspor'], $package->exclusions);
        $this->assertSame('/images/catalog-cover-sample.svg', $package->cover_image);
    }
}
