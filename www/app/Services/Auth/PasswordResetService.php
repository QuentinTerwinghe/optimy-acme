<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\Auth\PasswordResetServiceInterface;
use App\Jobs\Notification\SendForgotPasswordNotificationJob;
use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Service for handling password reset operations.
 */
class PasswordResetService implements PasswordResetServiceInterface
{
    /**
     * Create a password reset token for a user and dispatch notification job.
     *
     * @param string $email User's email address
     * @return bool True if token was created and notification dispatched
     * @throws \InvalidArgumentException If user not found
     */
    public function createTokenAndDispatchNotification(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // Generate a random token
        $token = Str::random(64);

        // Store hashed token in database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Dispatch job to RabbitMQ for async notification sending
        SendForgotPasswordNotificationJob::dispatch(
            (string) $user->id,
            $token,
            config('auth.passwords.users.expire', 60)
        );

        return true;
    }

    /**
     * Validate a password reset token.
     *
     * @param string $email User's email address
     * @param string $token Reset token
     * @return bool True if token is valid
     */
    public function validateToken(string $email, string $token): bool
    {
        /** @var \stdClass|null $resetRecord */
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord) {
            return false;
        }

        // Check if token has expired
        $expirationMinutes = config('auth.passwords.users.expire', 60);
        /** @var string $createdAtString */
        $createdAtString = $resetRecord->created_at;
        $createdAt = \Carbon\Carbon::parse($createdAtString);

        if ($createdAt->addMinutes($expirationMinutes)->isPast()) {
            // Token expired, delete it
            $this->deleteToken($email);
            return false;
        }

        // Verify token
        /** @var string $hashedToken */
        $hashedToken = $resetRecord->token;
        return Hash::check($token, $hashedToken);
    }

    /**
     * Reset user's password with the provided token.
     *
     * @param string $email User's email address
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return bool True if password was reset successfully
     * @throws \InvalidArgumentException If token is invalid or expired
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        // Validate token
        if (!$this->validateToken($email, $token)) {
            throw new \InvalidArgumentException('Invalid or expired password reset token');
        }

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        try {
            DB::beginTransaction();

            // Update password
            $user->password = Hash::make($newPassword);
            $user->save();

            // Delete the used token
            $this->deleteToken($email);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete password reset token.
     *
     * @param string $email User's email address
     * @return void
     */
    public function deleteToken(string $email): void
    {
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();
    }
}
