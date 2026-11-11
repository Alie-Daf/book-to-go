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
        // the documentation promises HTTPS: every link is https:// when the site runs in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
