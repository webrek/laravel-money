<?php

namespace Webrek\Money\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webrek\Money\Exceptions\CurrencyMismatchException;
use Webrek\Money\Money;
use Webrek\Money\Tests\Support\Product;
use Webrek\Money\Tests\TestCase;

class MoneyCastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->integer('price')->nullable();
            $table->integer('cost')->nullable();
            $table->string('cost_currency', 3)->nullable();
        });
    }

    public function test_it_casts_a_fixed_currency_column(): void
    {
        $product = Product::create(['price' => Money::of('19.99', 'USD')]);

        $this->assertSame(1999, $product->getRawOriginal('price'));

        $fresh = Product::firstOrFail();

        $this->assertInstanceOf(Money::class, $fresh->price);
        $this->assertSame(1999, $fresh->price->minorAmount);
        $this->assertSame('USD', $fresh->price->currency->code);
    }

    public function test_it_casts_a_multi_currency_column(): void
    {
        Product::create(['cost' => Money::of('15.50', 'EUR')]);

        $fresh = Product::firstOrFail();

        $this->assertSame(1550, $fresh->cost->minorAmount);
        $this->assertSame('EUR', $fresh->cost->currency->code);
        $this->assertSame('EUR', $fresh->getRawOriginal('cost_currency'));
    }

    public function test_it_handles_null(): void
    {
        $product = Product::create(['price' => null, 'cost' => null]);

        $fresh = Product::findOrFail($product->id);

        $this->assertNull($fresh->price);
        $this->assertNull($fresh->cost);
    }

    public function test_it_rejects_a_currency_that_does_not_match_a_fixed_column(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Product::create(['price' => Money::of('10.00', 'EUR')]);
    }
}
