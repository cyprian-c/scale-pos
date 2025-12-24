<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Sale\SaleService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SaleService::class, function ($app) {
            return new SaleService();
        });
    }

    public function boot(): void
    {
        // Add any bootstrapping code here
    }
}
