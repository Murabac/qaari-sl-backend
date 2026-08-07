<?php

use App\Http\Middleware\EnsureStaffUser;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Coolify / reverse proxies terminate TLS; trust X-Forwarded-* so HTTPS URLs generate correctly.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->alias([
            'staff' => EnsureStaffUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
