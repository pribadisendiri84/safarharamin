<?php

namespace Tests\Unit;

use App\Services\Arminareka\ArminarekaJadwalNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArminarekaJadwalNormalizerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_it_normalizes_known_periode_into_package_fields(): void
    {
        $normalized = (new ArminarekaJadwalNormalizer)->normalize([
            'external_id' => '5928',
            'row_number' => 1,
            'periode' => 'Muzdalifah JT CGK 12D JED (07/12/2026)',
            'departure_date_label' => '7 Dec 2026',
            'price_quad' => 38800000,
            'price_triple' => 40300000,
            'price_double' => 43400000,
            'quota' => 45,
            'seats_left' => 45,
        ]);

        $this->assertSame('arminareka:5928', $normalized['source_key']);
        $this->assertSame('umroh', $normalized['type']);
        $this->assertSame('Muzdalifah', $normalized['package_kind_label']);
        $this->assertSame($this->packageKindId('muzdalifah'), $normalized['package_kind_id']);
        $this->assertSame('jakarta', $normalized['departure_city']);
        $this->assertSame('jeddah', $normalized['arrival_city']);
        $this->assertSame('CGK', $normalized['origin_airport']);
        $this->assertSame('JED', $normalized['destination_airport']);
        $this->assertSame('2026-12-07', $normalized['departure_date']);
        $this->assertSame(12, $normalized['duration_days']);
        $this->assertSame('Lion Air', $normalized['airline']);
        $this->assertSame('draft', $normalized['status']);
        $this->assertSame([], $normalized['warnings']);

        $attributes = (new ArminarekaJadwalNormalizer)->toPackageAttributes($normalized);
        $this->assertSame('jakarta', $attributes['departure_city']);
        $this->assertSame('jeddah', $attributes['arrival_city']);
        $this->assertSame('quad', $attributes['room_type']);
    }

    #[Test]
    public function test_it_clamps_negative_seats_and_marks_fullbook_when_empty(): void
    {
        $normalized = (new ArminarekaJadwalNormalizer)->normalize([
            'external_id' => '6025',
            'row_number' => 25,
            'periode' => 'Mina JT KNO 12D JED (24/10/2026)',
            'departure_date_label' => '24 Oct 2026',
            'price_quad' => 41500000,
            'price_triple' => 43900000,
            'price_double' => 48600000,
            'quota' => 20,
            'seats_left' => -11,
        ]);

        $this->assertSame('medan', $normalized['departure_city']);
        $this->assertSame('jeddah', $normalized['arrival_city']);
        $this->assertSame(0, $normalized['seats_left']);
        $this->assertSame('fullbook', $normalized['status']);
        $this->assertNotEmpty($normalized['warnings']);
    }
}
