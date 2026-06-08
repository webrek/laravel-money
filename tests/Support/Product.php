<?php

namespace Webrek\Money\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Webrek\Money\Casts\MoneyCast;
use Webrek\Money\Money;

/**
 * @property Money|null $price
 * @property Money|null $cost
 */
class Product extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            // Single fixed currency: the column holds minor units only.
            'price' => MoneyCast::class . ':USD',
            // Multi-currency: companion "cost_currency" column holds the code.
            'cost' => MoneyCast::class,
        ];
    }
}
