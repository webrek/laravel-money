<?php

namespace Webrek\Money\Exceptions;

use RuntimeException;

class UnknownExchangeRateException extends RuntimeException
{
    public static function for(string $currency): self
    {
        return new self(sprintf('No exchange rate is configured for "%s".', $currency));
    }
}
