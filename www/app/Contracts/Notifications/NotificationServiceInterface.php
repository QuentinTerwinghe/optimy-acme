<?php

declare(strict_types=1);

namespace App\Contracts\Notifications;

use App\Enums\NotificationType;
use App\Models\Auth\User;

/**
 * Main notification service interface.
 * This is the facade that clients will use to send notifications.
 */
interface NotificationServiceInterface
{
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
    public function send(User $receiver, NotificationType $type, array $parameters): bool;
}
