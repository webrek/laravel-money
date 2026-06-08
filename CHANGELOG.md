# Changelog

All notable changes to `webrek/laravel-money` are documented here. The format
follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and the project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
