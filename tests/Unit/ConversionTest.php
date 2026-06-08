<?php

namespace Webrek\Money\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webrek\Money\ArrayExchangeRateProvider;
use Webrek\Money\Exceptions\UnknownExchangeRateException;
use Webrek\Money\Money;

class ConversionTest extends TestCase
{
    public function test_convert_to_with_an_explicit_rate(): void
    {
        $this->assertSame(920, Money::of('10.00', 'USD')->convertTo('EUR', '0.92')->minorAmount);
    }

    public function test_conversion_respects_target_scale(): void
    {
        // USD (2 decimals) -> JPY (0 decimals)
        $yen = Money::of('10.00', 'USD')->convertTo('JPY', '150');
        $this->assertSame(1500, $yen->minorAmount);
        $this->assertSame('JPY', $yen->currency->code);

        // JPY (0) -> USD (2)
        $usd = Money::of('1000', 'JPY')->convertTo('USD', '0.0067');
        $this->assertSame(670, $usd->minorAmount);
    }

    public function test_conversion_rounds(): void
    {
        $this->assertSame(333, Money::of('10.00', 'USD')->convertTo('EUR', '0.3333')->minorAmount);
    }

    public function test_convert_via_a_provider(): void
    {
        $rates = new ArrayExchangeRateProvider(['USD' => 1, 'EUR' => 0.92, 'MXN' => 17.5]);

        $this->assertSame(9200, Money::of('100', 'USD')->convert('EUR', $rates)->minorAmount);
    }

    public function test_provider_cross_rates(): void
    {
        $rates = new ArrayExchangeRateProvider(['USD' => 1, 'MXN' => 17.5]);

        // 175 MXN -> USD at 1/17.5 == 10.00
        $this->assertSame(1000, Money::of('175', 'MXN')->convert('USD', $rates)->minorAmount);
    }

    public function test_provider_returns_one_for_same_currency(): void
    {
        $rates = new ArrayExchangeRateProvider(['USD' => 1]);

        $this->assertSame('1', $rates->rate('USD', 'USD'));
        $this->assertSame(5000, Money::of('50', 'USD')->convert('USD', $rates)->minorAmount);
    }

    public function test_provider_throws_for_unknown_currency(): void
    {
        $this->expectException(UnknownExchangeRateException::class);

        (new ArrayExchangeRateProvider(['USD' => 1]))->rate('USD', 'EUR');
    }
}
