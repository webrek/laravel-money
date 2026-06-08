<?php

namespace Webrek\Money;

use Illuminate\Support\ServiceProvider;

class MoneyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/money.php', 'money');
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
