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
            'description' => 'Paket Muzdalifah premium dengan bimbingan ibadah lengkap.',
            'hotel_makkah' => 'Hilton Suites Makkah',
            'hotel_makkah_setaraf' => true,
            'hotel_madinah' => 'Front Taibah',
            'hotel_madinah_setaraf' => false,
            'facilities' => ['Tiket PP', 'Visa', 'Makan 3x'],
            'exclusions' => ['Paspor', 'Kursi roda'],
        ]);

        $merged = $kind->mergeMissingTemplateInto([
            'package_kind_id' => $kind->id,
            'description' => null,
            'hotel_makkah' => null,
            'hotel_makkah_setaraf' => false,
            'hotel_madinah' => null,
            'hotel_madinah_setaraf' => false,
            'facilities' => [],
            'exclusions' => [],
        ]);

        $this->assertSame('Paket Muzdalifah premium dengan bimbingan ibadah lengkap.', $merged['description']);
        $this->assertSame('Hilton Suites Makkah', $merged['hotel_makkah']);
        $this->assertTrue($merged['hotel_makkah_setaraf']);
        $this->assertSame('Front Taibah', $merged['hotel_madinah']);
        $this->assertSame(['Tiket PP', 'Visa', 'Makan 3x'], $merged['facilities']);
        $this->assertSame(['Paspor', 'Kursi roda'], $merged['exclusions']);
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
