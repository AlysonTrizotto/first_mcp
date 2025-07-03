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
        // Forçar URL correta em ambientes como Codespaces
        if (config('app.url')) {
            $appUrl = config('app.url');
            URL::forceRootUrl($appUrl);
            
            // Se a URL usa HTTPS, forçar esquema HTTPS
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
