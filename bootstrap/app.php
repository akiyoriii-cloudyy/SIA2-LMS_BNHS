<?php

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
        // Cloudflare Tunnel / reverse proxy: honor X-Forwarded-* so requests match APP_URL scheme.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'auth.api' => \App\Http\Middleware\JwtAuthMiddleware::class,
            'session.track' => \App\Http\Middleware\TrackUserSession::class,
            'log.activity' => \App\Http\Middleware\LogUserActivity::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\LoadUserRoles::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\TrackUserSession::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
