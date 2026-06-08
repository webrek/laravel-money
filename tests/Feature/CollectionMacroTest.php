<?php

namespace Webrek\Money\Tests\Feature;

use Illuminate\Support\Collection;
use Webrek\Money\Money;
use Webrek\Money\Tests\TestCase;

class CollectionMacroTest extends TestCase
{
    public function test_sum_money_over_a_collection_of_money(): void
    {
        $total = collect([
            Money::of('1.00', 'USD'),
            Money::of('2.50', 'USD'),
        ])->sumMoney();

        $this->assertInstanceOf(Money::class, $total);
        $this->assertSame(350, $total->minorAmount);
    }

    public function test_sum_money_by_key(): void
    {
        $total = collect([
            ['total' => Money::ofMinor(100, 'USD')],
            ['total' => Money::ofMinor(250, 'USD')],
        ])->sumMoney('total');

        $this->assertSame(350, $total->minorAmount);
    }

    public function test_sum_money_returns_null_for_an_empty_collection(): void
    {
        $this->assertNull(collect([])->sumMoney());
    }

    public function test_the_macro_is_registered(): void
    {
        $this->assertTrue(Collection::hasMacro('sumMoney'));
    }
}
