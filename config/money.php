<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default currency
    |--------------------------------------------------------------------------
    |
    | A convenience default your application can read when it needs to mint
    | money without an explicit currency. The package itself never assumes a
    | currency — you always pass one — but this gives you a single place to
    | configure your app's primary currency.
    |
    */

    'default_currency' => env('MONEY_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Default formatting locale
    |--------------------------------------------------------------------------
    |
    | The locale passed to Money::formatTo() when your application wants
    | locale-aware output. Null falls back to the application locale. Requires
    | the intl extension; without it, formatting degrades to Money::format().
    |
    */

    'locale' => env('MONEY_LOCALE'),

];
