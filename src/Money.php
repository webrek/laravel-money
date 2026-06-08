<?php

namespace Webrek\Money;

use JsonSerializable;
use Stringable;
use Webrek\Money\Contracts\ExchangeRateProvider;
use Webrek\Money\Exceptions\CurrencyMismatchException;
use Webrek\Money\Exceptions\InvalidAmountException;

/**
 * An immutable monetary amount, stored as an integer number of minor units
 * (e.g. cents) together with its currency. All arithmetic is exact integer
 * work; rounding only ever happens where you explicitly ask for it.
 */
final class Money implements JsonSerializable, Stringable
{
    private function __construct(
        public readonly int $minorAmount,
        public readonly Currency $currency,
    ) {}

    /**
     * Build from an integer number of minor units, e.g. Money::ofMinor(1099, 'USD') === $10.99.
     */
    public static function ofMinor(int $minorAmount, Currency|string $currency): self
    {
        return new self($minorAmount, self::currency($currency));
    }

    /**
     * Build from an amount in major units, e.g. Money::of('10.99', 'USD').
     * A value with more decimals than the currency allows is rounded.
     */
    public static function of(int|float|string $amount, Currency|string $currency, RoundingMode $rounding = RoundingMode::HALF_UP): self
    {
        $currency = self::currency($currency);

        return new self(
            self::scaleToInt(self::normalize($amount), $currency->minorUnit, $rounding),
            $currency,
        );
    }

    public static function zero(Currency|string $currency): self
    {
        return new self(0, self::currency($currency));
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getMinorAmount(): int
    {
        return $this->minorAmount;
    }

    /**
     * The amount in major units as a string, e.g. "1099" minor => "10.99".
     */
    public function toDecimal(): string
    {
        $scale = $this->currency->minorUnit;
        $sign = $this->minorAmount < 0 ? '-' : '';
        $digits = (string) abs($this->minorAmount);

        if ($scale === 0) {
            return $sign . $digits;
        }

        $digits = str_pad($digits, $scale + 1, '0', STR_PAD_LEFT);
        $whole = substr($digits, 0, -$scale);
        $fraction = substr($digits, -$scale);

        return $sign . $whole . '.' . $fraction;
    }

    public function isZero(): bool
    {
        return $this->minorAmount === 0;
    }

    public function isPositive(): bool
    {
        return $this->minorAmount > 0;
    }

    public function isNegative(): bool
    {
        return $this->minorAmount < 0;
    }

    public function plus(self $that): self
    {
        $this->assertSameCurrency($that);

        return new self($this->minorAmount + $that->minorAmount, $this->currency);
    }

    public function minus(self $that): self
    {
        $this->assertSameCurrency($that);

        return new self($this->minorAmount - $that->minorAmount, $this->currency);
    }

    public function times(int|float|string $multiplier, RoundingMode $rounding = RoundingMode::HALF_UP): self
    {
        $product = self::mul((string) $this->minorAmount, self::normalize($multiplier));

        return new self(self::roundToInt($product, $rounding), $this->currency);
    }

    public function dividedBy(int|float|string $divisor, RoundingMode $rounding = RoundingMode::HALF_UP): self
    {
        $divisor = self::normalize($divisor);

        if (self::isZeroString($divisor)) {
            throw new InvalidAmountException('Cannot divide money by zero.');
        }

        $quotient = self::div((string) $this->minorAmount, $divisor);

        return new self(self::roundToInt($quotient, $rounding), $this->currency);
    }

    /**
     * Convert to another currency using an explicit rate (units of the target
     * currency per one unit of this currency).
     */
    public function convertTo(Currency|string $to, int|float|string $rate, RoundingMode $rounding = RoundingMode::HALF_UP): self
    {
        $to = self::currency($to);
        $targetMajor = self::mul($this->toDecimal(), self::normalize($rate));

        return new self(self::scaleToInt($targetMajor, $to->minorUnit, $rounding), $to);
    }

    /**
     * Convert to another currency, resolving the rate from a provider.
     */
    public function convert(Currency|string $to, ExchangeRateProvider $rates, RoundingMode $rounding = RoundingMode::HALF_UP): self
    {
        $to = self::currency($to);

        return $this->convertTo($to, $rates->rate($this->currency->code, $to->code), $rounding);
    }

    public function abs(): self
    {
        return new self(abs($this->minorAmount), $this->currency);
    }

    public function negated(): self
    {
        return new self(-$this->minorAmount, $this->currency);
    }

    /**
     * Split the amount across the given integer ratios with no minor units lost.
     * The remainder is handed out one unit at a time, largest ratio first.
     *
     * @return list<self>
     */
    public function allocate(int ...$ratios): array
    {
        if ($ratios === []) {
            throw new InvalidAmountException('Cannot allocate without at least one ratio.');
        }

        $total = array_sum($ratios);

        if ($total <= 0) {
            throw new InvalidAmountException('The sum of allocation ratios must be positive.');
        }

        $remainder = $this->minorAmount;
        $shares = [];

        foreach ($ratios as $ratio) {
            $share = intdiv($this->minorAmount * $ratio, $total);
            $shares[] = $share;
            $remainder -= $share;
        }

        // Hand the leftover units to the largest ratios first for stable, fair splits.
        $order = array_keys($ratios);
        usort($order, fn (int $a, int $b): int => $ratios[$b] <=> $ratios[$a]);

        $step = $remainder <=> 0;

        for ($i = 0; $remainder !== 0; $i++) {
            $shares[$order[$i % count($order)]] += $step;
            $remainder -= $step;
        }

        return array_map(fn (int $share): self => new self($share, $this->currency), $shares);
    }

    /**
     * Split into N equal parts with no minor units lost.
     *
     * @return list<self>
     */
    public function split(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidAmountException('Cannot split money into fewer than one part.');
        }

        return $this->allocate(...array_fill(0, $parts, 1));
    }

    public function compareTo(self $that): int
    {
        $this->assertSameCurrency($that);

        return $this->minorAmount <=> $that->minorAmount;
    }

    public function isEqualTo(self $that): bool
    {
        return $this->currency->is($that->currency) && $this->minorAmount === $that->minorAmount;
    }

    public function isGreaterThan(self $that): bool
    {
        return $this->compareTo($that) > 0;
    }

    public function isGreaterThanOrEqualTo(self $that): bool
    {
        return $this->compareTo($that) >= 0;
    }

    public function isLessThan(self $that): bool
    {
        return $this->compareTo($that) < 0;
    }

    public function isLessThanOrEqualTo(self $that): bool
    {
        return $this->compareTo($that) <= 0;
    }

    /**
     * A locale-independent representation, e.g. "USD 1,234.56".
     */
    public function format(): string
    {
        $decimal = $this->toDecimal();
        $sign = str_starts_with($decimal, '-') ? '-' : '';
        $decimal = ltrim($decimal, '-');

        [$whole, $fraction] = array_pad(explode('.', $decimal), 2, '');
        $whole = number_format((float) $whole, 0, '.', ',');

        return $this->currency->code . ' ' . $sign . $whole . ($fraction !== '' ? '.' . $fraction : '');
    }

    /**
     * Locale-aware formatting via ext-intl, falling back to format() when the
     * intl extension is unavailable.
     */
    public function formatTo(string $locale): string
    {
        if (! extension_loaded('intl')) {
            return $this->format();
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency((float) $this->toDecimal(), $this->currency->code);
    }

    /**
     * @return array{amount: string, minorAmount: int, currency: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->toDecimal(),
            'minorAmount' => $this->minorAmount,
            'currency' => $this->currency->code,
        ];
    }

    public function __toString(): string
    {
        return $this->toDecimal() . ' ' . $this->currency->code;
    }

    private static function currency(Currency|string $currency): Currency
    {
        return $currency instanceof Currency ? $currency : new Currency($currency);
    }

    private function assertSameCurrency(self $that): void
    {
        if (! $this->currency->is($that->currency)) {
            throw CurrencyMismatchException::between($this->currency, $that->currency);
        }
    }

    /**
     * Normalise any numeric input to a canonical decimal string.
     */
    private static function normalize(int|float|string $amount): string
    {
        if (is_int($amount)) {
            return (string) $amount;
        }

        if (is_float($amount)) {
            if (! is_finite($amount)) {
                throw InvalidAmountException::for((string) $amount);
            }

            $amount = rtrim(rtrim(sprintf('%.14F', $amount), '0'), '.');

            return $amount === '' || $amount === '-' ? '0' : $amount;
        }

        $amount = trim($amount);

        if (preg_match('/^[+-]?\d+(\.\d+)?$/', $amount) !== 1) {
            throw InvalidAmountException::for($amount);
        }

        return ltrim($amount, '+');
    }

    /**
     * Shift the decimal point right by $scale places and round to an integer.
     */
    private static function scaleToInt(string $decimal, int $scale, RoundingMode $rounding): int
    {
        [$sign, $whole, $fraction] = self::parts($decimal);

        $fraction = str_pad($fraction, $scale, '0');
        $shifted = $whole . substr($fraction, 0, $scale);
        $remainder = substr($fraction, $scale);

        $value = ($sign . ($shifted === '' ? '0' : $shifted)) . ($remainder !== '' ? '.' . $remainder : '');

        return self::roundToInt($value, $rounding);
    }

    /**
     * Round a decimal string to an integer using the given mode.
     */
    private static function roundToInt(string $decimal, RoundingMode $rounding): int
    {
        [$sign, $whole, $fraction] = self::parts($decimal);

        $base = (int) ($whole === '' ? '0' : $whole);
        $fraction = rtrim($fraction, '0');

        if ($fraction === '') {
            return $sign === '-' ? -$base : $base;
        }

        $negative = $sign === '-';
        $increment = self::shouldRoundUp($fraction, $base, $negative, $rounding);
        $magnitude = $base + ($increment ? 1 : 0);

        return $negative ? -$magnitude : $magnitude;
    }

    private static function shouldRoundUp(string $fraction, int $base, bool $negative, RoundingMode $rounding): bool
    {
        $cmp = self::compareToHalf($fraction);

        return match ($rounding) {
            RoundingMode::UP => true,
            RoundingMode::DOWN => false,
            RoundingMode::CEILING => ! $negative,
            RoundingMode::FLOOR => $negative,
            RoundingMode::HALF_UP => $cmp >= 0,
            RoundingMode::HALF_DOWN => $cmp > 0,
            RoundingMode::HALF_EVEN => $cmp > 0 || ($cmp === 0 && $base % 2 === 1),
        };
    }

    /**
     * Compare the fractional part (digits after the point, no trailing zeros)
     * against one half: -1 below, 0 exactly, 1 above.
     */
    private static function compareToHalf(string $fraction): int
    {
        $first = (int) $fraction[0];

        if ($first < 5) {
            return -1;
        }

        if ($first > 5) {
            return 1;
        }

        return strlen($fraction) > 1 ? 1 : 0;
    }

    /**
     * @return array{0: string, 1: string, 2: string} sign, whole digits, fraction digits
     */
    private static function parts(string $decimal): array
    {
        $sign = str_starts_with($decimal, '-') ? '-' : '';
        $decimal = ltrim($decimal, '+-');

        [$whole, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');

        return [$sign, ltrim($whole, '0'), $fraction];
    }

    private static function isZeroString(string $decimal): bool
    {
        return preg_replace('/[^1-9]/', '', $decimal) === '';
    }

    private static function mul(string $a, string $b): string
    {
        if (function_exists('bcmul')) {
            return bcmul($a, $b, 20);
        }

        return rtrim(rtrim(sprintf('%.20F', (float) $a * (float) $b), '0'), '.');
    }

    private static function div(string $a, string $b): string
    {
        if (function_exists('bcdiv')) {
            return bcdiv($a, $b, 20);
        }

        return rtrim(rtrim(sprintf('%.20F', (float) $a / (float) $b), '0'), '.');
    }
}
