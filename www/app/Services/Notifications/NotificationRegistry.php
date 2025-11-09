<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationHandlerInterface;
use App\Contracts\Notifications\NotificationRegistryInterface;
use App\Enums\Notification\NotificationType;

/**
 * Registry for managing notification handlers.
 * Maps notification types to their respective handlers.
 */
class NotificationRegistry implements NotificationRegistryInterface
{
    /**
     * @var array<string, NotificationHandlerInterface>
     */
    private array $handlers = [];

    /**
     * Register a notification handler for a specific type.
     *
     * @param NotificationHandlerInterface $handler The handler to register
     * @return void
     */
    public function register(NotificationHandlerInterface $handler): void
    {
        $type = $handler->getNotificationType();
        $this->handlers[$type->value] = $handler;
    }

    /**
     * Get a handler for a specific notification type.
     *
     * @param NotificationType $type The notification type
     * @return NotificationHandlerInterface
     * @throws \InvalidArgumentException If no handler is registered for the type
     */
    public function getHandler(NotificationType $type): NotificationHandlerInterface
    {
        if (!$this->hasHandler($type)) {
            throw new \InvalidArgumentException(
                sprintf('No handler registered for notification type: %s', $type->value)
            );
        }

        return $this->handlers[$type->value];
    }

    /**
     * Check if a handler is registered for a notification type.
     *
     * @param NotificationType $type The notification type
     * @return bool
     */
    public function hasHandler(NotificationType $type): bool
    {
        return isset($this->handlers[$type->value]);
    }
}
