<?php

namespace Webrek\Money\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webrek\Money\ArrayExchangeRateProvider;
use Webrek\Money\Exceptions\InvalidAmountException;
use Webrek\Money\Money;
use Webrek\Money\RoundingMode;

class MutationCoverageTest extends TestCase
{
    public function test_accessors(): void
    {
        $money = Money::ofMinor(1099, 'USD');

        $this->assertSame('USD', $money->getCurrency()->code);
        $this->assertSame(1099, $money->getMinorAmount());
    }

    public function test_half_down_distinguishes_exact_half_from_above(): void
    {
        $this->assertSame(200, Money::of('2.005', 'USD', RoundingMode::HALF_DOWN)->minorAmount);
        $this->assertSame(201, Money::of('2.0051', 'USD', RoundingMode::HALF_DOWN)->minorAmount);
    }

    public function test_half_even_rounds_to_the_even_neighbour(): void
    {
        $this->assertSame(200, Money::of('2.005', 'USD', RoundingMode::HALF_EVEN)->minorAmount); // 200 is even
        $this->assertSame(202, Money::of('2.025', 'USD', RoundingMode::HALF_EVEN)->minorAmount); // 202 is even
        $this->assertSame(204, Money::of('2.035', 'USD', RoundingMode::HALF_EVEN)->minorAmount); // 203 is odd -> 204
    }

    public function test_below_half_rounds_down(): void
    {
        $this->assertSame(200, Money::of('2.0049', 'USD', RoundingMode::HALF_UP)->minorAmount);
    }

    public function test_invalid_string_amount_is_rejected(): void
    {
        $this->expectException(InvalidAmountException::class);

        Money::of('not-a-number', 'USD');
    }

    public function test_non_finite_float_is_rejected(): void
    {
        $this->expectException(InvalidAmountException::class);

        Money::of(INF, 'USD');
    }

    public function test_leading_plus_and_zeros_are_normalised(): void
    {
        $this->assertSame(500, Money::of('+5', 'USD')->minorAmount);
        $this->assertSame(750, Money::of('007.50', 'USD')->minorAmount);
    }

    public function test_float_amounts_are_normalised(): void
    {
        $this->assertSame(200, Money::of(2.0, 'USD')->minorAmount);
        $this->assertSame(1050, Money::of(10.50, 'USD')->minorAmount);
    }

    public function test_division_by_a_zero_decimal_string_is_rejected(): void
    {
        $this->expectException(InvalidAmountException::class);

        Money::ofMinor(100, 'USD')->dividedBy('0.0');
    }

    public function test_exchange_rates_accept_lowercase_codes(): void
    {
        $rates = new ArrayExchangeRateProvider(['USD' => 1, 'EUR' => 0.92]);

        $this->assertSame(9200, Money::of('100', 'usd')->convert('eur', $rates)->minorAmount);
    }

    public function test_format_handles_sign_and_grouping(): void
    {
        $this->assertSame('USD -1,234.56', Money::ofMinor(-123456, 'USD')->format());
        $this->assertSame('1234.56 USD', (string) Money::ofMinor(123456, 'USD'));
    }
}
