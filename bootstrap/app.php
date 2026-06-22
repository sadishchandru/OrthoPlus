<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\ResolveUser;
use App\Http\Middleware\CheckRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    // Trust all proxies for Render HTTPS
    $middleware->trustProxies(at: '*');

    // Exclude API from CSRF — Laravel 11 correct syntax
    $middleware->validateCsrfTokens(except: [
        'api/*',
        'sanctum/*',
        '*',  // temporary: exclude ALL to confirm CSRF is the issue
    ]);

    // Middleware aliases
    $middleware->alias([
        'role'         => CheckRole::class,
        'resolve.user' => ResolveUser::class,
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();