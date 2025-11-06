<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Attempt to authenticate a user with the given credentials.
     *
     * @param string $username
     * @param string $password
     * @param bool $remember
     * @return bool
     */
    public function attemptLogin(string $username, string $password, bool $remember = false): bool
    {
        return Auth::attempt(
            ['email' => $username, 'password' => $password],
            $remember
        );
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function getAuthenticatedUser()
    {
        return Auth::user();
    }

    /**
     * Log out the current user.
     *
     * @return void
     */
    public function logout(): void
    {
        Auth::logout();
    }

    /**
     * Check if a user is currently authenticated.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return Auth::check();
    }

    /**
     * Get the authenticated user's ID.
     *
     * @return int|string|null
     */
    public function getAuthenticatedUserId(): int|string|null
    {
        return Auth::id();
    }
}
