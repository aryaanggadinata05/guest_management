<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        // Paksa skema HTTPS saat diakses di environment production atau melalui proxy/HTTPS
        if (
            app()->isProduction() ||
            str_starts_with((string) config('app.url'), 'https://') ||
            request()->header('x-forwarded-proto') === 'https' ||
            request()->isSecure() ||
            str_contains(request()->header('host', ''), 'ngrok') ||
            str_contains(request()->header('host', ''), 'ngrok-free.app') ||
            str_contains(request()->header('host', ''), 'ngrok.io')
        ) {
            URL::forceScheme('https');
        }
    }
}
