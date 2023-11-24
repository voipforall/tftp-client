<?php

namespace VoIPforAll\TFTPClient;

use Illuminate\Support\ServiceProvider;

class TFTPClientServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('tftp-client.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'tftp-client');

        $this->app->singleton('tftp-client', function () {
            return new TFTPClient;
        });
    }
}
