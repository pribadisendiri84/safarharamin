<?php

namespace Tests\Unit;

use App\Models\PackageKind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackageKindTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_it_merges_missing_content_from_kind_template(): void
    {
        $kind = PackageKind::query()->where('slug', 'muzdalifah')->firstOrFail();
        $kind->update([
            'description' => 'Paket {type_short} {package_kind} {duration_days} Hari, maskapai {airline}.',
            'hotel_makkah' => 'Hilton Suites Makkah',
            'hotel_makkah_setaraf' => true,
            'hotel_madinah' => 'Front Taibah',
            'hotel_madinah_setaraf' => false,
            'facilities' => ['Tiket PP', 'Visa', 'Makan 3x'],
            'exclusions' => ['Paspor', 'Kursi roda'],
            'cover_image' => '/images/catalog-cover-sample.svg',
        ]);

        $merged = $kind->mergeMissingTemplateInto([
            'package_kind_id' => $kind->id,
            'type' => 'umroh',
            'duration_days' => 12,
            'airline' => 'Lion Air',
            'description' => null,
            'hotel_makkah' => null,
            'hotel_makkah_setaraf' => false,
            'hotel_madinah' => null,
            'hotel_madinah_setaraf' => false,
            'facilities' => [],
            'exclusions' => [],
            'cover_image' => null,
        ]);

        $this->assertSame('Paket Umroh Muzdalifah 12 Hari, maskapai Lion Air.', $merged['description']);
        $this->assertSame('Hilton Suites Makkah', $merged['hotel_makkah']);
        $this->assertTrue($merged['hotel_makkah_setaraf']);
        $this->assertSame('Front Taibah', $merged['hotel_madinah']);
        $this->assertSame(['Tiket PP', 'Visa', 'Makan 3x'], $merged['facilities']);
        $this->assertSame(['Paspor', 'Kursi roda'], $merged['exclusions']);
        $this->assertSame('/images/catalog-cover-sample.svg', $merged['cover_image']);
    }

    #[Test]
    public function test_it_does_not_override_existing_package_content(): void
    {
        $kind = PackageKind::query()->where('slug', 'mina')->firstOrFail();
        $kind->update([
            'description' => 'Template Mina',
            'facilities' => ['Template fasilitas'],
        ]);

        $merged = $kind->mergeMissingTemplateInto([
            'description' => 'Deskripsi paket existing',
            'facilities' => ['Fasilitas existing'],
            'exclusions' => [],
        ]);

        $this->assertSame('Deskripsi paket existing', $merged['description']);
        $this->assertSame(['Fasilitas existing'], $merged['facilities']);
    }
}
