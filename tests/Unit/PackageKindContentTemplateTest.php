<?php

namespace Tests\Unit;

use App\Models\PackageKind;
use App\Support\PackageKindContentTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackageKindContentTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_it_renders_description_placeholders_from_package_data(): void
    {
        $kind = PackageKind::query()->where('slug', 'arafah')->firstOrFail();

        $rendered = PackageKindContentTemplate::render(
            PackageKindContentTemplate::DEFAULT_DESCRIPTION,
            [
                'type' => 'umroh',
                'duration_days' => 9,
                'airline' => 'Lion Air',
                'hotel_makkah' => 'Hilton',
                'hotel_madinah' => 'Front Taibah',
            ],
            $kind,
        );

        $this->assertSame(
            'Paket Umroh Arafah 9 Hari dengan hotel bintang 5, maskapai Lion Air, dan pendampingan muthawwif berbahasa Indonesia.',
            $rendered,
        );
    }
}
