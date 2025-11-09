<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationRegistryInterface;
use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\NotificationType;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Log;

/**
 * Main notification service.
 * Delegates notification sending to appropriate handlers via the registry.
 */
class NotificationService implements NotificationServiceInterface
{
    /**
     * @param NotificationRegistryInterface $registry
     */
    public function __construct(
        private readonly NotificationRegistryInterface $registry
    ) {
    }

    /**
     * Send a notification to a user.
     *
     * @param User $receiver The user who will receive the notification
     * @param NotificationType $type The type of notification to send
     * @param array<string, mixed> $parameters Parameters to populate the notification content
     * @return bool True if notification was sent successfully
     * @throws \InvalidArgumentException If notification type is not supported
     * @throws \Exception If notification sending fails
     */
    public function send(User $receiver, NotificationType $type, array $parameters): bool
    {
        Log::info('Processing notification', [
            'type' => $type->value,
            'receiver_id' => $receiver->id,
            'receiver_email' => $receiver->email,
        ]);

        try {
            $handler = $this->registry->getHandler($type);
            return $handler->handle($receiver, $parameters);
        } catch (\InvalidArgumentException $e) {
            Log::error('Unsupported notification type', [
                'type' => $type->value,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'type' => $type->value,
                'receiver_id' => $receiver->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
