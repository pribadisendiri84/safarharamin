<?php

namespace Tests\Unit;

use App\Services\Arminareka\ArminarekaRouteParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArminarekaRouteParserTest extends TestCase
{
    #[Test]
    public function test_it_parses_origin_destination_and_duration_from_jeddah_route(): void
    {
        $route = ArminarekaRouteParser::parse('Muzdalifah JT CGK 12D JED (07/12/2026)');

        $this->assertSame('CGK', $route['origin_airport']);
        $this->assertSame('JED', $route['destination_airport']);
        $this->assertSame('jakarta', $route['departure_city']);
        $this->assertSame('jeddah', $route['arrival_city']);
        $this->assertSame(12, $route['duration_days']);
    }

    #[Test]
    public function test_it_parses_padang_to_jeddah_route(): void
    {
        $route = ArminarekaRouteParser::parse('JT PDG 12D JED');

        $this->assertSame('PDG', $route['origin_airport']);
        $this->assertSame('JED', $route['destination_airport']);
        $this->assertSame('padang', $route['departure_city']);
        $this->assertSame('jeddah', $route['arrival_city']);
        $this->assertSame(12, $route['duration_days']);
    }

    #[Test]
    public function test_it_parses_madinah_destination_route(): void
    {
        $route = ArminarekaRouteParser::parse('AROFAH A GA CGK 12D MED (05/12/2026)');

        $this->assertSame('CGK', $route['origin_airport']);
        $this->assertSame('MED', $route['destination_airport']);
        $this->assertSame('jakarta', $route['departure_city']);
        $this->assertSame('madinah', $route['arrival_city']);
        $this->assertSame(12, $route['duration_days']);
    }
}
