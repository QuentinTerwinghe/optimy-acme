<?php

namespace App\Contracts\Auth;

use Illuminate\Http\Request;

interface RateLimitServiceInterface
{
    /**
     * Check if the request has too many login attempts.
     *
     * @param Request $request
     * @return bool
     */
    public function tooManyAttempts(Request $request): bool;

    /**
     * Increment the login attempts for the request.
     *
     * @param Request $request
     * @return void
     */
    public function incrementAttempts(Request $request): void;

    /**
     * Get the number of seconds until the rate limiter is available again.
     *
     * @param Request $request
     * @return int
     */
    public function availableIn(Request $request): int;

    /**
     * Clear the login attempts for the request.
     *
     * @param Request $request
     * @return void
     */
    public function clearAttempts(Request $request): void;
}
