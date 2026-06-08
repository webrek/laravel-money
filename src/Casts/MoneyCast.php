<?php

namespace Webrek\Money\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Webrek\Money\Currency;
use Webrek\Money\Exceptions\CurrencyMismatchException;
use Webrek\Money\Money;

/**
 * Casts an integer column of minor units to a {@see Money} object.
 *
 * Single-currency column (currency fixed in the cast definition):
 *
 *     protected $casts = ['price' => MoneyCast::class.':USD'];
 *
 * Multi-currency (a companion "{column}_currency" string column holds the code):
 *
 *     protected $casts = ['price' => MoneyCast::class];
 *
 * @implements CastsAttributes<Money, Money|int|float|string>
 */
class MoneyCast implements CastsAttributes
{
    public function __construct(protected ?string $currency = null) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::ofMinor((int) $value, $this->resolveCurrency($key, $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return $this->currency === null ? [$key => null, $this->currencyKey($key) => null] : [$key => null];
        }

        if (! $value instanceof Money) {
            $value = Money::of($value, $this->currency ?? $this->resolveCurrency($key, $attributes));
        }

        if ($this->currency !== null && $value->currency->code !== strtoupper($this->currency)) {
            throw CurrencyMismatchException::between(new Currency($this->currency), $value->currency);
        }

        if ($this->currency !== null) {
            return [$key => $value->minorAmount];
        }

        return [
            $key => $value->minorAmount,
            $this->currencyKey($key) => $value->currency->code,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function resolveCurrency(string $key, array $attributes): Currency
    {
        if ($this->currency !== null) {
            return new Currency($this->currency);
        }

        $code = $attributes[$this->currencyKey($key)] ?? null;

        return new Currency(is_string($code) ? $code : '');
    }

    protected function currencyKey(string $key): string
    {
        return $key . '_currency';
    }
}
