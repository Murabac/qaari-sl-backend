<?php

use App\Http\Middleware\EnsureStaffUser;
use App\Http\Middleware\SetLocale;
use App\Support\LastExceptionProbe;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        $exceptions->reportable(function (\Throwable $e): void {
            LastExceptionProbe::capture($e);
        });

        // Livewire shows a blank "500 | SERVER ERROR" dialog. For staff sessions,
        // return the real exception class/message so we can fix production without SSH.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->hasHeader('X-Livewire')) {
                return null;
            }

            $user = $request->user();
            if (! $user || ! method_exists($user, 'isSuperAdmin') || ! $user->isSuperAdmin()) {
                return null;
            }

            return response(
                '<html><body style="font-family:monospace;padding:24px;background:#111;color:#f5f5f5">'
                .'<h1 style="color:#f87171">500 — '.$e::class.'</h1>'
                .'<p><strong>'.e($e->getMessage()).'</strong></p>'
                .'<p>'.e($e->getFile()).':'.$e->getLine().'</p>'
                .'<pre style="white-space:pre-wrap;font-size:12px">'.e($e->getTraceAsString()).'</pre>'
                .'</body></html>',
                500,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        });
    })->create();
