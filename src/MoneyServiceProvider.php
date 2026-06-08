<?php

namespace Webrek\Money;

use Illuminate\Support\Collection;
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
        $this->registerCollectionMacro();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/money.php' => $this->app->configPath('money.php'),
            ], 'money-config');
        }
    }

    private function registerCollectionMacro(): void
    {
        if (Collection::hasMacro('sumMoney')) {
            return;
        }

        Collection::macro('sumMoney', function (?string $key = null): ?Money {
            /** @var Collection<array-key, mixed> $this */
            $values = $key === null ? $this->all() : $this->pluck($key)->all();
            $monies = array_filter($values, fn ($value): bool => $value instanceof Money);

            return $monies === [] ? null : Money::sum($monies);
        });
    }
}
