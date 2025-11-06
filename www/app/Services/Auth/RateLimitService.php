<?php

namespace App\Services\Auth;

use App\Contracts\Auth\RateLimitServiceInterface;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RateLimitService implements RateLimitServiceInterface
{
    /**
     * Create a new RateLimitService instance.
     *
     * @param RateLimiter $rateLimiter
     */
    public function __construct(
        private readonly RateLimiter $rateLimiter
    ) {}

    /**
     * Check if the request has too many login attempts.
     *
     * @param Request $request
     * @return bool
     */
    public function tooManyAttempts(Request $request): bool
    {
        return $this->rateLimiter->tooManyAttempts(
            $this->throttleKey($request),
            5
        );
    }

    /**
     * Increment the login attempts for the request.
     *
     * @param Request $request
     * @return void
     */
    public function incrementAttempts(Request $request): void
    {
        $this->rateLimiter->hit(
            $this->throttleKey($request),
            60
        );
    }

    /**
     * Get the number of seconds until the rate limiter is available again.
     *
     * @param Request $request
     * @return int
     */
    public function availableIn(Request $request): int
    {
        return $this->rateLimiter->availableIn(
            $this->throttleKey($request)
        );
    }

    /**
     * Clear the login attempts for the request.
     *
     * @param Request $request
     * @return void
     */
    public function clearAttempts(Request $request): void
    {
        $this->rateLimiter->clear(
            $this->throttleKey($request)
        );
    }

    /**
     * Get the throttle key for the given request.
     *
     * @param Request $request
     * @return string
     */
    private function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->input('username')) . '|' . $request->ip()
        );
    }
}
