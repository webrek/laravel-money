<?php

namespace Webrek\Money\Exceptions;

use InvalidArgumentException;

class InvalidCurrencyException extends InvalidArgumentException
{
    public static function for(string $code): self
    {
        return new self(sprintf('"%s" is not a valid ISO 4217 currency code.', $code));
    }
}
