<?php

namespace Webrek\Money\Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Webrek\Money\Rules\CurrencyCode;
use Webrek\Money\Tests\TestCase;

class CurrencyCodeRuleTest extends TestCase
{
    private function passes(mixed $value): bool
    {
        return Validator::make(['currency' => $value], ['currency' => new CurrencyCode])->passes();
    }

    public function test_it_accepts_valid_codes(): void
    {
        $this->assertTrue($this->passes('USD'));
        $this->assertTrue($this->passes('mxn'));
    }

    public function test_it_rejects_invalid_codes(): void
    {
        $this->assertFalse($this->passes('US1'));
        $this->assertFalse($this->passes('DOLLARS'));
        $this->assertFalse($this->passes(123));
    }
}
