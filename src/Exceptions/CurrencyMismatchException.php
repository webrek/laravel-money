<?php

namespace Webrek\Money\Exceptions;

use LogicException;
use Webrek\Money\Currency;

class CurrencyMismatchException extends LogicException
{
    public static function between(Currency $a, Currency $b): self
    {
        return new self(sprintf(
            'Cannot operate on money in different currencies: %s and %s.',
            $a->code,
            $b->code,
        ));
    }
}
