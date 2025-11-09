<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

use App\Models\Auth\User;

/**
 * Interface for password reset service.
 */
interface PasswordResetServiceInterface
{
    /**
     * Create a password reset token for a user and dispatch notification job.
     *
     * @param string $email User's email address
     * @return bool True if token was created and notification dispatched
     * @throws \InvalidArgumentException If user not found
     */
    public function createTokenAndDispatchNotification(string $email): bool;

    /**
     * Validate a password reset token.
     *
     * @param string $email User's email address
     * @param string $token Reset token
     * @return bool True if token is valid
     */
    public function validateToken(string $email, string $token): bool;

    /**
     * Reset user's password with the provided token.
     *
     * @param string $email User's email address
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return bool True if password was reset successfully
     * @throws \InvalidArgumentException If token is invalid or expired
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool;

    /**
     * Delete password reset token.
     *
     * @param string $email User's email address
     * @return void
     */
    public function deleteToken(string $email): void;
}
