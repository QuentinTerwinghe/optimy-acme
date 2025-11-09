<?php

declare(strict_types=1);

namespace App\Contracts\Notifications;

use App\Enums\Notification\NotificationType;

/**
 * Registry interface for managing notification handlers.
 * Allows registering and retrieving handlers for different notification types.
 */
interface NotificationRegistryInterface
{
    /**
     * Register a notification handler for a specific type.
     *
     * @param NotificationHandlerInterface $handler The handler to register
     * @return void
     */
    public function register(NotificationHandlerInterface $handler): void;

    /**
     * Get a handler for a specific notification type.
     *
     * @param NotificationType $type The notification type
     * @return NotificationHandlerInterface
     * @throws \InvalidArgumentException If no handler is registered for the type
     */
    public function getHandler(NotificationType $type): NotificationHandlerInterface;

    /**
     * Check if a handler is registered for a notification type.
     *
     * @param NotificationType $type The notification type
     * @return bool
     */
    public function hasHandler(NotificationType $type): bool;
}
