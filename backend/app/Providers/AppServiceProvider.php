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
        if (env('VERCEL') || env('VERCEL_ENV') || env('VERCEL_URL')) {
            URL::forceScheme('https');

            $host = env('VERCEL_PROJECT_PRODUCTION_URL') ?: env('VERCEL_URL');

            if ($host) {
                URL::forceRootUrl('https://' . preg_replace('/^https?:\/\//', '', $host));
            }
        }
    }
}
