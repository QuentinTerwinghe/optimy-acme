<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationHandlerInterface;
use App\Enums\Notification\NotificationType;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for notification handlers.
 * Provides common functionality and structure for all handlers.
 */
abstract class AbstractNotificationHandler implements NotificationHandlerInterface
{
    /**
     * Get the notification type this handler supports.
     */
    abstract public function getNotificationType(): NotificationType;

    /**
     * Handle the notification sending logic.
     *
     * @param User $receiver The user who will receive the notification
     * @param array<string, mixed> $parameters Parameters to populate the notification content
     * @return bool True if notification was sent successfully
     * @throws \Exception If notification sending fails
     */
    public function handle(User $receiver, array $parameters): bool
    {
        try {
            $this->validate($receiver, $parameters);

            $success = $this->send($receiver, $parameters);

            if ($success) {
                $this->logSuccess($receiver, $parameters);
            } else {
                $this->logFailure($receiver, $parameters);
            }

            return $success;
        } catch (\Exception $e) {
            $this->logError($receiver, $parameters, $e);
            throw $e;
        }
    }

    /**
     * Validate the receiver and parameters before sending.
     * Override this method to add custom validation.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters
     * @return void
     * @throws \InvalidArgumentException If validation fails
     */
    protected function validate(User $receiver, array $parameters): void
    {
        if (!$receiver->email) {
            throw new \InvalidArgumentException('Receiver must have an email address');
        }
    }

    /**
     * Send the actual notification.
     * This method must be implemented by concrete handlers.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters
     * @return bool
     */
    abstract protected function send(User $receiver, array $parameters): bool;

    /**
     * Log successful notification sending.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters
     * @return void
     */
    protected function logSuccess(User $receiver, array $parameters): void
    {
        Log::info('Notification sent successfully', [
            'type' => $this->getNotificationType()->value,
            'receiver_id' => $receiver->id,
            'receiver_email' => $receiver->email,
        ]);
    }

    /**
     * Log failed notification sending.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters
     * @return void
     */
    protected function logFailure(User $receiver, array $parameters): void
    {
        Log::warning('Notification sending failed', [
            'type' => $this->getNotificationType()->value,
            'receiver_id' => $receiver->id,
            'receiver_email' => $receiver->email,
        ]);
    }

    /**
     * Log error during notification sending.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters
     * @param \Exception $exception
     * @return void
     */
    protected function logError(User $receiver, array $parameters, \Exception $exception): void
    {
        Log::error('Notification sending error', [
            'type' => $this->getNotificationType()->value,
            'receiver_id' => $receiver->id,
            'receiver_email' => $receiver->email,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
