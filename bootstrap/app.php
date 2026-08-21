<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use App\Jobs\CheckProviderHealthJob;
use App\Jobs\PollNumberOrdersJob;
use App\Jobs\ReleaseExpiredNumbersJob;
use App\Jobs\SyncNumberCatalogJob;
use App\Jobs\SyncOrderStatusesJob;
use App\Jobs\SyncProviderServicesJob;
use App\Models\Provider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        $middleware->trustProxies(at: [
            '127.0.0.1',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '100.64.0.0/10',
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->call(function () {
            foreach (Provider::query()->forChannel(Provider::CHANNEL_BOOST)->active()->get() as $provider) {
                SyncProviderServicesJob::dispatch($provider->id);
                CheckProviderHealthJob::dispatch($provider->id);
            }
        })->name('smm.daily-provider-sync')->dailyAt('01:00')->withoutOverlapping();

        $schedule->call(function () {
            foreach (Provider::query()->forChannel(Provider::CHANNEL_BOOST)->active()->get() as $provider) {
                CheckProviderHealthJob::dispatch($provider->id);
            }
        })->name('smm.hourly-provider-health')->hourlyAt(15)->withoutOverlapping();

        $schedule->job(SyncOrderStatusesJob::class)->name('smm.order-status-sync')->everyFiveMinutes()->withoutOverlapping();

        $schedule->job(PollNumberOrdersJob::class)->name('numbers.poll-orders')->everyMinute()->withoutOverlapping();
        $schedule->job(ReleaseExpiredNumbersJob::class)->name('numbers.release-expired')->everyMinute()->withoutOverlapping();
        $schedule->job(SyncNumberCatalogJob::class)->name('numbers.catalog-sync')->dailyAt('02:00')->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
