<?php

namespace Khalidmaquilang\SensorsPlus;

use Illuminate\Support\ServiceProvider;
use Khalidmaquilang\SensorsPlus\Commands\CopyAssetsCommand;

class SensorsPlusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SensorsPlus::class, function () {
            return new SensorsPlus();
        });
    }

    public function boot(): void
    {
        // Register plugin hook commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CopyAssetsCommand::class,
            ]);
        }
    }
}