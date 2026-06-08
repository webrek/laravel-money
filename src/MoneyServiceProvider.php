<?php

namespace Webrek\Money;

use Illuminate\Support\ServiceProvider;
use Webrek\Money\Contracts\ExchangeRateProvider;

class MoneyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/money.php', 'money');

        $this->app->singleton(ExchangeRateProvider::class, fn ($app): ArrayExchangeRateProvider => new ArrayExchangeRateProvider($app['config']->get('money.exchange.rates', [])));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/money.php' => $this->app->configPath('money.php'),
            ], 'money-config');
        }
    }
}
