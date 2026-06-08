<?php

namespace Webrek\Money;

use Stringable;
use Webrek\Money\Exceptions\InvalidCurrencyException;

/**
 * An ISO 4217 currency, aware of how many minor units (decimal places) it has.
 */
final class Currency implements Stringable
{
    /**
     * Currencies whose minor unit is not the usual 2. Everything else defaults
     * to 2 decimal places.
     *
     * @var array<string, int>
     */
    private const MINOR_UNITS = [
        'BHD' => 3, 'IQD' => 3, 'JOD' => 3, 'KWD' => 3, 'LYD' => 3,
        'OMR' => 3, 'TND' => 3,
        'CLF' => 4, 'UYW' => 4,
        'BIF' => 0, 'CLP' => 0, 'DJF' => 0, 'GNF' => 0, 'ISK' => 0,
        'JPY' => 0, 'KMF' => 0, 'KRW' => 0, 'PYG' => 0, 'RWF' => 0,
        'UGX' => 0, 'VND' => 0, 'VUV' => 0, 'XAF' => 0, 'XOF' => 0,
        'XPF' => 0,
    ];

    public readonly string $code;

    public readonly int $minorUnit;

    public function __construct(string $code)
    {
        $code = strtoupper(trim($code));

        if (preg_match('/^[A-Z]{3}$/', $code) !== 1) {
            throw InvalidCurrencyException::for($code);
        }

        $this->code = $code;
        $this->minorUnit = self::MINOR_UNITS[$code] ?? 2;
    }

    public static function of(string $code): self
    {
        return new self($code);
    }

    public function is(self $other): bool
    {
        return $this->code === $other->code;
    }

    /**
     * The number of minor units in one major unit, e.g. 100 for USD, 1 for JPY.
     */
    public function subunits(): int
    {
        return 10 ** $this->minorUnit;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
