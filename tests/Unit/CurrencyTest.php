<?php

namespace Webrek\Money\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webrek\Money\Currency;
use Webrek\Money\Exceptions\InvalidCurrencyException;

class CurrencyTest extends TestCase
{
    public function test_it_knows_minor_units(): void
    {
        $this->assertSame(2, Currency::of('USD')->minorUnit);
        $this->assertSame(2, Currency::of('MXN')->minorUnit);
        $this->assertSame(0, Currency::of('JPY')->minorUnit);
        $this->assertSame(3, Currency::of('BHD')->minorUnit);
        $this->assertSame(4, Currency::of('CLF')->minorUnit);
    }

    public function test_it_normalises_the_code(): void
    {
        $currency = Currency::of(' usd ');

        $this->assertSame('USD', $currency->code);
        $this->assertSame('USD', (string) $currency);
    }

    public function test_it_computes_subunits(): void
    {
        $this->assertSame(100, Currency::of('USD')->subunits());
        $this->assertSame(1, Currency::of('JPY')->subunits());
        $this->assertSame(1000, Currency::of('KWD')->subunits());
    }

    public function test_it_compares_currencies(): void
    {
        $this->assertTrue(Currency::of('USD')->is(Currency::of('usd')));
        $this->assertFalse(Currency::of('USD')->is(Currency::of('EUR')));
    }

    public function test_it_rejects_invalid_codes(): void
    {
        $this->expectException(InvalidCurrencyException::class);

        Currency::of('US1');
    }
}
