<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Hosts such as Vercel / Render terminate TLS before PHP.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
