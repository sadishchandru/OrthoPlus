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
        // Railway terminates TLS at the proxy — force https URLs in production
        // so assets/links aren't emitted as http (mixed-content).
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
