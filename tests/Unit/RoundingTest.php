<?php

namespace Webrek\Money\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webrek\Money\Money;
use Webrek\Money\RoundingMode;

class RoundingTest extends TestCase
{
    public function test_half_up_and_half_down(): void
    {
        $this->assertSame(201, Money::of('2.005', 'USD', RoundingMode::HALF_UP)->minorAmount);
        $this->assertSame(200, Money::of('2.005', 'USD', RoundingMode::HALF_DOWN)->minorAmount);
    }

    public function test_half_even_breaks_ties_to_even(): void
    {
        $this->assertSame(200, Money::of('2.005', 'USD', RoundingMode::HALF_EVEN)->minorAmount);
        $this->assertSame(202, Money::of('2.015', 'USD', RoundingMode::HALF_EVEN)->minorAmount);
    }

    public function test_up_and_down(): void
    {
        $this->assertSame(201, Money::of('2.001', 'USD', RoundingMode::UP)->minorAmount);
        $this->assertSame(200, Money::of('2.009', 'USD', RoundingMode::DOWN)->minorAmount);
    }

    public function test_ceiling_and_floor(): void
    {
        $this->assertSame(201, Money::of('2.001', 'USD', RoundingMode::CEILING)->minorAmount);
        $this->assertSame(-200, Money::of('-2.001', 'USD', RoundingMode::CEILING)->minorAmount);
        $this->assertSame(-201, Money::of('-2.001', 'USD', RoundingMode::FLOOR)->minorAmount);
        $this->assertSame(200, Money::of('2.009', 'USD', RoundingMode::FLOOR)->minorAmount);
    }

    public function test_rounding_applies_to_multiplication(): void
    {
        $this->assertSame(1648, Money::ofMinor(1099, 'USD')->times('1.5', RoundingMode::DOWN)->minorAmount);
        $this->assertSame(1649, Money::ofMinor(1099, 'USD')->times('1.5', RoundingMode::UP)->minorAmount);
    }
}
