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
        // SPA mode - let Vue Router handle routing
        // For API requests, return proper JSON responses without redirects
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // Don't redirect API requests
            }
            return '/login';
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // Don't redirect API requests
            }
            return '/dashboard';
        });

        // Ensure CSRF protection is enabled for web routes
        $middleware->validateCsrfTokens(except: [
            // Add any routes that should be excluded from CSRF protection
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
