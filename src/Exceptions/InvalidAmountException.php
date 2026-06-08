<?php

namespace Webrek\Money\Exceptions;

use InvalidArgumentException;

class InvalidAmountException extends InvalidArgumentException
{
    public static function for(string $amount): self
    {
        return new self(sprintf('"%s" is not a valid numeric amount.', $amount));
    }
}
