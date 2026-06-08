<?php

namespace App\Providers;

use App\Services\License\FeatureGate;
use App\Services\License\LicenseService;
use Illuminate\Support\ServiceProvider;

class LicenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LicenseService::class, function () {
            return new LicenseService;
        });

        $this->app->singleton(FeatureGate::class, function ($app) {
            return new FeatureGate($app->make(LicenseService::class));
        });
    }

    public function boot(): void
    {
        $this->app->make(LicenseService::class)->load();
    }
}
