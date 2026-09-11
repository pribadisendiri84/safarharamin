<?php

namespace Tests\Unit;

use App\Services\Arminareka\ArminarekaPeriodeParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArminarekaPeriodeParserTest extends TestCase
{
    #[Test]
    public function test_it_splits_base_title_and_embedded_date(): void
    {
        $parsed = ArminarekaPeriodeParser::parse('Muzdalifah JT CGK 12D JED (07/12/2026)');

        $this->assertSame('Muzdalifah JT CGK 12D JED', $parsed['base_title']);
        $this->assertSame('2026-12-07', $parsed['embedded_date']);
    }

    #[Test]
    public function test_it_keeps_title_when_no_date_suffix(): void
    {
        $parsed = ArminarekaPeriodeParser::parse('Muzdalifah JT CGK 12D JED');

        $this->assertSame('Muzdalifah JT CGK 12D JED', $parsed['base_title']);
        $this->assertNull($parsed['embedded_date']);
    }
}
