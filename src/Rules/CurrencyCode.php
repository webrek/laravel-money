<?php

namespace Webrek\Money\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webrek\Money\Currency;
use Webrek\Money\Exceptions\InvalidCurrencyException;

/**
 * Validates that a value is a usable ISO 4217 currency code.
 */
class CurrencyCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a currency code.');

            return;
        }

        try {
            new Currency($value);
        } catch (InvalidCurrencyException) {
            $fail('The :attribute must be a valid ISO 4217 currency code.');
        }
    }
}
