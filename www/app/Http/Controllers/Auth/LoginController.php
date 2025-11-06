<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Auth\AuthServiceInterface;
use App\Contracts\Auth\RateLimitServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param AuthServiceInterface $authService
     * @param RateLimitServiceInterface $rateLimitService
     */
    public function __construct(
        private readonly AuthServiceInterface $authService,
        private readonly RateLimitServiceInterface $rateLimitService
    ) {}

    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        /** @phpstan-ignore argument.type */
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $this->ensureIsNotRateLimited($request);

        $credentials = [
            'email' => $request->validated('username'),
            'password' => $request->validated('password'),
        ];

        $remember = $request->boolean('remember');

        if (! $this->authService->attemptLogin($credentials, $remember)) {
            $this->rateLimitService->incrementAttempts($request);

            throw ValidationException::withMessages([
                'username' => __('The provided credentials are incorrect.'),
            ]);
        }

        $this->rateLimitService->clearAttempts($request);

        $request->session()->regenerate();

        // Return JSON for API requests, redirect for web requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login successful',
                'user' => $this->authService->getAuthenticatedUser(),
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Return JSON for API requests, redirect for web requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logout successful',
            ]);
        }

        return redirect()->route('login.form');
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @param Request $request
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! $this->rateLimitService->tooManyAttempts($request)) {
            return;
        }

        event(new Lockout($request));

        $seconds = $this->rateLimitService->availableIn($request);

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
}
