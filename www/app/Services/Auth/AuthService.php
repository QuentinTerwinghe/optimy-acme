<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\Auth\AuthServiceInterface;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;

/**
 * Auth Service
 *
 * Handles authentication operations
 * Follows Single Responsibility Principle - only authentication logic
 */
class AuthService implements AuthServiceInterface
{
    /**
     * Create a new AuthService instance.
     *
     * @param StatefulGuard $auth
     */
    public function __construct(
        private readonly StatefulGuard $auth
    ) {}

    /**
     * Attempt to authenticate a user with the given credentials.
     *
     * @param array<string, mixed> $credentials
     * @param bool $remember
     * @return bool
     */
    public function attemptLogin(array $credentials, bool $remember = false): bool
    {
        return $this->auth->attempt($credentials, $remember);
    }

    /**
     * Get the currently authenticated user.
     *
     * @return User|null
     */
    public function getAuthenticatedUser(): ?User
    {
        $user = $this->auth->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->auth->logout();
    }

    /**
     * Check if a user is currently authenticated.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->auth->check();
    }

    /**
     * Get the ID of the currently authenticated user.
     *
     * @return int|null
     */
    public function getAuthenticatedUserId(): ?int
    {
        $id = $this->auth->id();

        return is_int($id) ? $id : null;
    }
}
