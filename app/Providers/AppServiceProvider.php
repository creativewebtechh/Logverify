<?php

namespace App\Providers;

use App\Services\BrandingService;
use App\Services\MailSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        try {
            if (Schema::hasTable('settings')) {
                Config::set('app.name', BrandingService::siteName());
                MailSettings::apply();
            }
        } catch (\Throwable) {
            // Database is not available yet (e.g. during migrations or
            // install) — fall back to the environment defaults.
        }
    }
}
