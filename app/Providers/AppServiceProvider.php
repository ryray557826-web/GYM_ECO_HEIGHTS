<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Safety net: Force all generated routes, redirects, and forms to HTTPS in production
        if (app()->environment('production') || str_contains(env('APP_URL', ''), 'https://')) {
            URL::forceScheme('https');
        }
    }
}