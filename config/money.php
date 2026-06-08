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

    /*
    |--------------------------------------------------------------------------
    | Exchange rates
    |--------------------------------------------------------------------------
    |
    | Rates for the default ArrayExchangeRateProvider, given relative to a common
    | base currency (give the base a rate of 1). The cross rate from A to B is
    | rate(B) / rate(A). Resolve the provider via the ExchangeRateProvider
    | contract, or pass your own implementation to Money::convert().
    |
    |   'rates' => ['USD' => 1, 'EUR' => 0.92, 'MXN' => 17.5],
    |
    */

    'exchange' => [
        'rates' => [],
    ],

];
