# Changelog

All notable changes to `webrek/laravel-money` are documented here. The format
follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and the project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2026-06-16

### Added

- Laravel 13 support. The package now installs on Laravel 12 and 13 (PHP 8.2+).

## [1.2.0] - 2026-06-07

### Added

- `Money::sum()`, `Money::min()` and `Money::max()` over any iterable of money.
- `Money::percentage()` for tax/discount-style calculations.
- A `sumMoney()` Collection macro (optionally by key) returning the total, or
  null for an empty collection.

## [1.1.0] - 2026-06-07

### Added

- Currency conversion: `Money::convertTo($currency, $rate)` with an explicit
  rate and `Money::convert($currency, $provider)` via an `ExchangeRateProvider`.
- `ExchangeRateProvider` contract and an `ArrayExchangeRateProvider` driver that
  resolves cross rates from a base-relative rate map (configurable, bound to the
  contract).

## [1.0.0] - 2026-06-07

### Added

- Immutable `Money` value object stored as integer minor units with a currency.
- Exact integer arithmetic: `plus`, `minus`, `times`, `dividedBy`, `abs`,
  `negated`, with explicit `RoundingMode` where rounding is unavoidable.
- `allocate()` and `split()` that distribute an amount with no minor units lost.
- Comparison helpers and `compareTo`, plus `JsonSerializable` and `Stringable`.
- `Currency` value object aware of ISO 4217 minor units.
- `MoneyCast` for Eloquent (single fixed-currency column or a multi-currency
  column with a companion currency column).
- `CurrencyCode` validation rule.
- Locale-independent `format()` and intl-backed `formatTo()`.
