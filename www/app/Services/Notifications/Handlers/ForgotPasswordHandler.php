<?php

declare(strict_types=1);

namespace App\Services\Notifications\Handlers;

use App\Enums\Notification\NotificationType;
use App\Mail\Auth\ForgotPasswordMail;
use App\Models\Auth\User;
use App\Services\Notifications\AbstractNotificationHandler;
use Illuminate\Support\Facades\Mail;

/**
 * Handler for forgot password notifications.
 * Sends password reset emails with token links.
 */
class ForgotPasswordHandler extends AbstractNotificationHandler
{
    /**
     * Get the notification type this handler supports.
     */
    public function getNotificationType(): NotificationType
    {
        return NotificationType::FORGOT_PASSWORD;
    }

    /**
     * Validate the receiver and parameters before sending.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters
     * @return void
     * @throws \InvalidArgumentException If validation fails
     */
    protected function validate(User $receiver, array $parameters): void
    {
        parent::validate($receiver, $parameters);

        if (!isset($parameters['token'])) {
            throw new \InvalidArgumentException('Missing required parameter: token');
        }

        if (!is_string($parameters['token']) || empty($parameters['token'])) {
            throw new \InvalidArgumentException('Parameter "token" must be a non-empty string');
        }

        if (isset($parameters['expiration_minutes']) && !is_int($parameters['expiration_minutes'])) {
            throw new \InvalidArgumentException('Parameter "expiration_minutes" must be an integer');
        }
    }

    /**
     * Send the password reset notification.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters Expected keys:
     *                                         - token: string (required) - Password reset token
     *                                         - expiration_minutes: int (optional) - Token expiration in minutes, defaults to config
     * @return bool
     */
    protected function send(User $receiver, array $parameters): bool
    {
        try {
            $token = $parameters['token'];
            $expirationMinutes = $parameters['expiration_minutes']
                ?? config('auth.passwords.users.expire', 60);

            // Build the password reset URL
            $resetUrl = $this->buildResetUrl($receiver->email, $token);

            // Send the email
            Mail::to($receiver->email)->send(
                new ForgotPasswordMail(
                    user: $receiver,
                    resetUrl: $resetUrl,
                    expirationMinutes: $expirationMinutes
                )
            );

            return true;
        } catch (\Exception $e) {
            $this->logError($receiver, $parameters, $e);
            return false;
        }
    }

    /**
     * Build the password reset URL.
     *
     * @param string $email
     * @param string $token
     * @return string
     */
    private function buildResetUrl(string $email, string $token): string
    {
        return route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]);
    }
}
