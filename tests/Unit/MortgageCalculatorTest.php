<?php

namespace Tests\Unit;

use App\Services\MortgageCalculator;
use PHPUnit\Framework\TestCase;

class MortgageCalculatorTest extends TestCase
{
    public function test_monthly_payment_is_positive_and_covers_principal(): void
    {
        $result = MortgageCalculator::breakdown(500_000_000, 20, 15, 8);

        $this->assertSame(100_000_000, $result['down_payment']);
        $this->assertSame(400_000_000, $result['principal']);
        $this->assertGreaterThan(0, $result['monthly']);
        $this->assertGreaterThan($result['principal'], $result['total']);
    }
}
