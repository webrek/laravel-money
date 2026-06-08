<?php

namespace Webrek\Money\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webrek\Money\Exceptions\CurrencyMismatchException;
use Webrek\Money\Exceptions\InvalidAmountException;
use Webrek\Money\Money;

class AggregateTest extends TestCase
{
    public function test_sum(): void
    {
        $total = Money::sum([
            Money::of('1.00', 'USD'),
            Money::of('2.00', 'USD'),
            Money::of('3.50', 'USD'),
        ]);

        $this->assertSame(650, $total->minorAmount);
        $this->assertSame('USD', $total->currency->code);
    }

    public function test_min_and_max(): void
    {
        $monies = [Money::of('5', 'USD'), Money::of('1', 'USD'), Money::of('9', 'USD')];

        $this->assertSame(100, Money::min($monies)->minorAmount);
        $this->assertSame(900, Money::max($monies)->minorAmount);
    }

    public function test_sum_rejects_mixed_currencies(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Money::sum([Money::of('1', 'USD'), Money::of('1', 'EUR')]);
    }

    public function test_sum_rejects_an_empty_set(): void
    {
        $this->expectException(InvalidAmountException::class);

        Money::sum([]);
    }

    public function test_percentage(): void
    {
        $this->assertSame(160, Money::of('10.00', 'USD')->percentage(16)->minorAmount);
        $this->assertSame(83, Money::of('10.00', 'USD')->percentage('8.25')->minorAmount);
        $this->assertSame(1000, Money::of('10.00', 'USD')->percentage(100)->minorAmount);
    }
}
