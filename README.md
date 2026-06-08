# Laravel Money

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webrek/laravel-money.svg?style=flat-square)](https://packagist.org/packages/webrek/laravel-money)
[![Total Downloads](https://img.shields.io/packagist/dt/webrek/laravel-money.svg?style=flat-square)](https://packagist.org/packages/webrek/laravel-money)
[![Tests](https://img.shields.io/github/actions/workflow/status/webrek/laravel-money/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/webrek/laravel-money/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/packagist/php-v/webrek/laravel-money.svg?style=flat-square)](https://php.net)
[![License](https://img.shields.io/packagist/l/webrek/laravel-money.svg?style=flat-square)](LICENSE)

An immutable money value object for Laravel. Amounts are stored as an integer
number of minor units (cents), arithmetic is exact, and rounding only ever
happens where you explicitly ask for it.

## Quickstart

```bash
composer require webrek/laravel-money
```

```php
use Webrek\Money\Money;

$price = Money::of('19.99', 'USD');     // from major units
$tax   = $price->times('0.16');          // 16% — rounded HALF_UP to 3.20

$total = $price->plus($tax);             // USD 23.19
$total->format();                        // "USD 23.19"
$total->minorAmount;                     // 2319
```

## Why not just use a decimal column and floats?

Because money and binary floats do not mix. `0.1 + 0.2` is not `0.3`, and a cent
that drifts in a loop becomes a reconciliation ticket. The robust approach —
used by every serious payments system — is to store money as an integer count of
the smallest unit (cents, fils, yen) and never let a float near it.

This package gives you that as a first-class type:

- **Integer-exact.** Every amount is an `int` of minor units plus a `Currency`.
  Addition and subtraction can't lose precision because there's no precision to
  lose.
- **Rounding is explicit.** The only operations that can produce a fraction of a
  cent — multiplication and division — take a `RoundingMode`, defaulting to
  `HALF_UP`. Nothing rounds behind your back.
- **No money is created or destroyed when splitting.** `allocate()` and
  `split()` distribute every last minor unit.
- **Currency-aware.** Operations across currencies throw instead of silently
  producing nonsense, and each currency knows its own scale (USD has 2 decimals,
  JPY has 0, BHD has 3).

It has no dependency on `moneyphp/money` — it's a focused, Laravel-native type.

## Building money

```php
Money::of('10.99', 'USD');     // major units (string is safest)
Money::of(10.99, 'USD');       // float accepted, rounded to the currency scale
Money::ofMinor(1099, 'USD');   // minor units directly
Money::zero('USD');

// Currency scale is respected automatically:
Money::of('1000', 'JPY')->minorAmount;   // 1000  (JPY has no minor unit)
Money::of('1.234', 'BHD')->minorAmount;  // 1234  (BHD has 3)
```

## Arithmetic

```php
$a = Money::of('10.00', 'USD');
$b = Money::of('2.50', 'USD');

$a->plus($b);              // 12.50
$a->minus($b);            // 7.50
$a->times(3);             // 30.00
$a->dividedBy(3);         // 3.33  (HALF_UP)
$a->negated();            // -10.00
$a->abs();

$a->plus(Money::of('1', 'EUR'));   // throws CurrencyMismatchException
```

### Rounding modes

`times()` and `dividedBy()` accept any [`RoundingMode`](src/RoundingMode.php):
`UP`, `DOWN`, `CEILING`, `FLOOR`, `HALF_UP` (default), `HALF_DOWN`, `HALF_EVEN`
(banker's rounding).

```php
use Webrek\Money\RoundingMode;

Money::ofMinor(1099, 'USD')->times('1.5', RoundingMode::DOWN); // 16.48
Money::ofMinor(1099, 'USD')->times('1.5', RoundingMode::UP);   // 16.49
```

## Splitting without losing cents

```php
// Split a bill three ways — the leftover cent is handed out, nothing vanishes.
$shares = Money::ofMinor(100, 'USD')->split(3);
// [USD 0.34, USD 0.33, USD 0.33]   (sums back to exactly 1.00)

// Allocate by ratio (e.g. a 70/30 revenue share):
Money::ofMinor(100, 'USD')->allocate(7, 3);
// [USD 0.70, USD 0.30]
```

The remainder is handed to the largest ratios first, so splits are stable and
fair, and `array_sum` of the parts always equals the original.

## Comparison

```php
$a->isEqualTo($b);
$a->isGreaterThan($b);
$a->isGreaterThanOrEqualTo($b);
$a->isLessThan($b);
$a->isLessThanOrEqualTo($b);
$a->compareTo($b);        // -1 | 0 | 1
$a->isZero();
$a->isPositive();
$a->isNegative();
```

## Eloquent casting

Store minor units in an integer column and cast it to `Money`.

**Single currency** — the column holds minor units; the currency is fixed in the
cast:

```php
use Webrek\Money\Casts\MoneyCast;

protected function casts(): array
{
    return ['price' => MoneyCast::class . ':USD'];
}
```

```php
$product->price = Money::of('19.99', 'USD');
$product->save();                 // stores 1999 in `price`
$product->price->format();        // "USD 19.99"
```

**Multi-currency** — add a companion `{column}_currency` string column and omit
the code:

```php
protected function casts(): array
{
    return ['cost' => MoneyCast::class];   // reads/writes `cost` and `cost_currency`
}
```

```php
$product->cost = Money::of('15.50', 'EUR');   // stores 1550 + "EUR"
```

Assigning a currency that doesn't match a fixed-currency column throws a
`CurrencyMismatchException`.

## Validation

```php
use Webrek\Money\Rules\CurrencyCode;

$request->validate([
    'currency' => ['required', new CurrencyCode],
]);
```

## Formatting & serialization

```php
$money = Money::ofMinor(123456, 'USD');

$money->format();             // "USD 1,234.56"   (locale-independent)
$money->formatTo('en_US');    // "$1,234.56"      (requires ext-intl)
$money->toDecimal();          // "1234.56"
(string) $money;              // "1234.56 USD"

json_encode($money);
// {"amount":"1234.56","minorAmount":123456,"currency":"USD"}
```

`formatTo()` uses the intl extension; without it, it falls back to `format()`.

## Requirements

| Component | Version |
| --------- | ------- |
| PHP | 8.2+ |
| Laravel | 12.x |
| ext-intl | optional, for `formatTo()` |
| ext-bcmath | optional, for exact large-scale multiplication/division |

## Testing

```bash
composer install
composer test
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

Please review the [security policy](SECURITY.md) before reporting a
vulnerability.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
