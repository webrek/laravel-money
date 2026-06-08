<?php

namespace Webrek\Money;

use Webrek\Money\Contracts\ExchangeRateProvider;
use Webrek\Money\Exceptions\UnknownExchangeRateException;

/**
 * Resolves exchange rates from a static map of currency => rate relative to a
 * common base. The cross rate from A to B is rate(B) / rate(A), so the base
 * currency itself needs no special treatment — just give it a rate of 1.
 */
class ArrayExchangeRateProvider implements ExchangeRateProvider
{
    /**
     * @param  array<string, int|float|string>  $rates
     */
    public function __construct(private readonly array $rates) {}

    public function rate(string $from, string $to): string
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return '1';
        }

        $fromRate = $this->rates[$from] ?? throw UnknownExchangeRateException::for($from);
        $toRate = $this->rates[$to] ?? throw UnknownExchangeRateException::for($to);

        return self::divide((string) $toRate, (string) $fromRate);
    }

    private static function divide(string $a, string $b): string
    {
        if (function_exists('bcdiv')) {
            return bcdiv($a, $b, 20);
        }

        return rtrim(rtrim(sprintf('%.20F', (float) $a / (float) $b), '0'), '.');
    }
}
