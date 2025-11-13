<?php

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
        // Configure guest and authenticated user redirects
        $middleware->redirectGuestsTo(fn (Request $request) => route('login.form'));
        $middleware->redirectUsersTo(fn (Request $request) => route('dashboard'));

        // Ensure CSRF protection is enabled for web routes
        $middleware->validateCsrfTokens(except: [
            // Add any routes that should be excluded from CSRF protection
        ]);

        // Register middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
