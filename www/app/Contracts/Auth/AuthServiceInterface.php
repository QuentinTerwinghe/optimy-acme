<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

use App\Models\User;

/**
 * Auth Service Interface
 *
 * Defines the contract for authentication operations
 * Follows Single Responsibility Principle - handles only authentication
 */
interface AuthServiceInterface
{
    /**
     * Attempt to authenticate a user with the given credentials.
     *
     * @param array<string, mixed> $credentials
     * @param bool $remember
     * @return bool
     */
    public function attemptLogin(array $credentials, bool $remember = false): bool;

    /**
     * Get the currently authenticated user.
     *
     * @return User|null
     */
    public function getAuthenticatedUser(): ?User;

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout(): void;

    /**
     * Check if a user is currently authenticated.
     *
     * @return bool
     */
    public function isAuthenticated(): bool;

    /**
     * Get the ID of the currently authenticated user.
     *
     * @return int|null
     */
    public function getAuthenticatedUserId(): ?int;
}
