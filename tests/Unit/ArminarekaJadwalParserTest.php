<?php

namespace Tests\Unit;

use App\Services\Arminareka\ArminarekaJadwalParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArminarekaJadwalParserTest extends TestCase
{
    #[Test]
    public function test_it_parses_jadwal_table_rows(): void
    {
        $html = file_get_contents(base_path('tests/Fixtures/arminareka-jadwal-page.html'));
        $rows = (new ArminarekaJadwalParser)->parseHtml($html ?: '');

        $this->assertCount(4, $rows);
        $this->assertSame('5928', $rows[0]['external_id']);
        $this->assertSame('Muzdalifah JT CGK 12D JED (07/12/2026)', $rows[0]['periode']);
        $this->assertSame(38800000, $rows[0]['price_quad']);
        $this->assertSame(40300000, $rows[0]['price_triple']);
        $this->assertSame(43400000, $rows[0]['price_double']);
        $this->assertNull($rows[2]['price_double']);
        $this->assertSame(-11, $rows[3]['seats_left']);
    }

    #[Test]
    public function test_it_returns_empty_array_for_blank_html(): void
    {
        $this->assertSame([], (new ArminarekaJadwalParser)->parseHtml(''));
    }
}
