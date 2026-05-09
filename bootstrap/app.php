<?php

use App\Http\Middleware\EnsureOperationalCentral;
use App\Http\Middleware\InitializeOperationalTenancy;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
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
