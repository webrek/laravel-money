<?php

namespace Webrek\Money\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webrek\Money\Exceptions\CurrencyMismatchException;
use Webrek\Money\Exceptions\InvalidAmountException;
use Webrek\Money\Money;

class MoneyTest extends TestCase
{
    public function test_it_builds_from_minor_units(): void
    {
        $money = Money::ofMinor(1099, 'USD');

        $this->assertSame(1099, $money->minorAmount);
        $this->assertSame('USD', $money->currency->code);
        $this->assertSame('10.99', $money->toDecimal());
    }

    public function test_it_builds_from_major_units(): void
    {
        $this->assertSame(1099, Money::of('10.99', 'USD')->minorAmount);
        $this->assertSame(1000, Money::of(10, 'USD')->minorAmount);
        $this->assertSame(1050, Money::of(10.5, 'USD')->minorAmount);
    }

    public function test_it_rounds_excess_decimals_when_building(): void
    {
        $this->assertSame(1100, Money::of('10.999', 'USD')->minorAmount);
        $this->assertSame(1099, Money::of('10.994', 'USD')->minorAmount);
    }

    public function test_it_respects_currency_scale(): void
    {
        $this->assertSame(1000, Money::of('1000', 'JPY')->minorAmount);
        $this->assertSame('1000', Money::of('1000', 'JPY')->toDecimal());
        $this->assertSame(1234, Money::of('1.234', 'BHD')->minorAmount);
        $this->assertSame('1.234', Money::of('1.234', 'BHD')->toDecimal());
    }

    public function test_it_handles_negative_amounts(): void
    {
        $this->assertSame('-10.99', Money::ofMinor(-1099, 'USD')->toDecimal());
        $this->assertTrue(Money::ofMinor(-1, 'USD')->isNegative());
        $this->assertTrue(Money::ofMinor(1, 'USD')->isPositive());
        $this->assertTrue(Money::zero('USD')->isZero());
    }

    public function test_it_adds_and_subtracts(): void
    {
        $a = Money::of('10.00', 'USD');
        $b = Money::of('2.50', 'USD');

        $this->assertSame(1250, $a->plus($b)->minorAmount);
        $this->assertSame(750, $a->minus($b)->minorAmount);
    }

    public function test_it_rejects_operations_across_currencies(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Money::of('10', 'USD')->plus(Money::of('10', 'EUR'));
    }

    public function test_it_multiplies(): void
    {
        $this->assertSame(999, Money::ofMinor(333, 'USD')->times(3)->minorAmount);
        $this->assertSame(1649, Money::ofMinor(1099, 'USD')->times('1.5')->minorAmount);
    }

    public function test_it_divides(): void
    {
        $this->assertSame(333, Money::ofMinor(1000, 'USD')->dividedBy(3)->minorAmount);
        $this->assertSame(500, Money::ofMinor(1000, 'USD')->dividedBy(2)->minorAmount);
    }

    public function test_it_rejects_division_by_zero(): void
    {
        $this->expectException(InvalidAmountException::class);

        Money::ofMinor(1000, 'USD')->dividedBy(0);
    }

    public function test_it_negates_and_absolutes(): void
    {
        $this->assertSame(-500, Money::ofMinor(500, 'USD')->negated()->minorAmount);
        $this->assertSame(500, Money::ofMinor(-500, 'USD')->abs()->minorAmount);
    }

    public function test_it_compares(): void
    {
        $small = Money::of('5', 'USD');
        $big = Money::of('10', 'USD');

        $this->assertTrue($big->isGreaterThan($small));
        $this->assertTrue($small->isLessThan($big));
        $this->assertTrue($big->isGreaterThanOrEqualTo(Money::of('10', 'USD')));
        $this->assertTrue($small->isLessThanOrEqualTo(Money::of('5', 'USD')));
        $this->assertSame(0, $big->compareTo(Money::of('10', 'USD')));
        $this->assertTrue($big->isEqualTo(Money::of('10', 'USD')));
        $this->assertFalse($big->isEqualTo(Money::of('10', 'EUR')));
    }

    public function test_it_is_immutable(): void
    {
        $money = Money::ofMinor(1000, 'USD');
        $money->plus(Money::ofMinor(500, 'USD'));

        $this->assertSame(1000, $money->minorAmount);
    }

    public function test_it_formats_locale_independently(): void
    {
        $this->assertSame('USD 1,234.56', Money::ofMinor(123456, 'USD')->format());
        $this->assertSame('USD -1,234.56', Money::ofMinor(-123456, 'USD')->format());
        $this->assertSame('JPY 1,000', Money::ofMinor(1000, 'JPY')->format());
    }

    public function test_it_serialises_to_json(): void
    {
        $this->assertSame(
            ['amount' => '10.99', 'minorAmount' => 1099, 'currency' => 'USD'],
            Money::ofMinor(1099, 'USD')->jsonSerialize(),
        );
    }

    public function test_it_casts_to_string(): void
    {
        $this->assertSame('10.99 USD', (string) Money::ofMinor(1099, 'USD'));
    }
}
