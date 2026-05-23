<?php

use App\Http\Middleware\EnsureOperationalCentral;
use App\Http\Middleware\InitializeOperationalTenancy;
use App\Jobs\SyncTraccarPositions;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $interval = (int) config('traccar.positions_sync_interval', 60);
        $schedule->job(new SyncTraccarPositions())->everyMinute()->when($interval <= 60);
        $schedule->job(new SyncTraccarPositions())->everyFiveMinutes()->when($interval > 60);
    })
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'operational.tenant' => InitializeOperationalTenancy::class,
            'operational.central' => EnsureOperationalCentral::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'integrations/calls/incident-intake',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
