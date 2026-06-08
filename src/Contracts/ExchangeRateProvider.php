<?php

namespace Webrek\Money\Contracts;

interface ExchangeRateProvider
{
    /**
     * The exchange rate from one currency to another, as a decimal string —
     * how many units of $to equal one unit of $from.
     */
    public function rate(string $from, string $to): string;
}
