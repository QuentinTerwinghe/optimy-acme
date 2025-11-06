<?php

declare(strict_types=1);

namespace App\Contracts\Notifications;

use App\Enums\NotificationType;
use App\Models\User;

/**
 * Interface for notification handlers (Strategy pattern).
 * Each notification type will have its own handler implementation.
 */
interface NotificationHandlerInterface
{
    /**
     * Get the notification type this handler supports.
     */
    public function getNotificationType(): NotificationType;

    /**
     * Handle the notification sending logic.
     *
     * @param User $receiver The user who will receive the notification
     * @param array<string, mixed> $parameters Parameters to populate the notification content
     * @return bool True if notification was sent successfully
     * @throws \Exception If notification sending fails
     */
    public function handle(User $receiver, array $parameters): bool;
}
