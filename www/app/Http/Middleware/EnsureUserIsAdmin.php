<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure User Is Admin Middleware
 *
 * Checks if the authenticated user has the wildcard (*) permission,
 * which grants full admin access to the application.
 *
 * This middleware follows the Single Responsibility Principle.
 */
class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            abort(401, 'Unauthenticated');
        }

        // Check if user has the wildcard (*) permission
        if (!$request->user()->hasPermissionTo('*')) {
            abort(403, 'Access denied. Administrator privileges required.');
        }

        return $next($request);
    }
}
