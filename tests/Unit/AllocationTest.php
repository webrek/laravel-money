<?php

namespace Webrek\Money\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webrek\Money\Exceptions\InvalidAmountException;
use Webrek\Money\Money;

class AllocationTest extends TestCase
{
    /**
     * @param  list<Money>  $shares
     * @return list<int>
     */
    private function minors(array $shares): array
    {
        return array_map(fn (Money $m): int => $m->minorAmount, $shares);
    }

    public function test_it_allocates_without_losing_minor_units(): void
    {
        $shares = Money::ofMinor(100, 'USD')->allocate(1, 1, 1);

        $this->assertSame([34, 33, 33], $this->minors($shares));
        $this->assertSame(100, array_sum($this->minors($shares)));
    }

    public function test_it_allocates_by_ratio(): void
    {
        $shares = Money::ofMinor(100, 'USD')->allocate(7, 3);

        $this->assertSame([70, 30], $this->minors($shares));
    }

    public function test_it_hands_the_remainder_to_the_largest_ratio_first(): void
    {
        $shares = Money::ofMinor(101, 'USD')->allocate(1, 3);

        $this->assertSame([25, 76], $this->minors($shares));
        $this->assertSame(101, array_sum($this->minors($shares)));
    }

    public function test_it_allocates_negative_amounts(): void
    {
        $shares = Money::ofMinor(-100, 'USD')->allocate(1, 1, 1);

        $this->assertSame([-34, -33, -33], $this->minors($shares));
        $this->assertSame(-100, array_sum($this->minors($shares)));
    }

    public function test_it_splits_into_equal_parts(): void
    {
        $shares = Money::ofMinor(100, 'USD')->split(3);

        $this->assertSame([34, 33, 33], $this->minors($shares));
    }

    public function test_it_rejects_empty_allocation(): void
    {
        $this->expectException(InvalidAmountException::class);

        Money::ofMinor(100, 'USD')->allocate();
    }

    public function test_it_rejects_non_positive_ratio_totals(): void
    {
        $this->expectException(InvalidAmountException::class);

        Money::ofMinor(100, 'USD')->allocate(0, 0);
    }
}
