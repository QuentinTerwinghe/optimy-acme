<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Contracts\Notifications\NotificationHandlerInterface;
use App\Enums\Notification\NotificationType;
use App\Services\Notifications\NotificationRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NotificationRegistry.
 */
class NotificationRegistryTest extends TestCase
{
    private NotificationRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new NotificationRegistry();
    }

    public function test_register_adds_handler_successfully(): void
    {
        // Arrange
        $handler = $this->createMockHandler(NotificationType::FORGOT_PASSWORD);

        // Act
        $this->registry->register($handler);

        // Assert
        $this->assertTrue($this->registry->hasHandler(NotificationType::FORGOT_PASSWORD));
    }

    public function test_get_handler_returns_registered_handler(): void
    {
        // Arrange
        $handler = $this->createMockHandler(NotificationType::FORGOT_PASSWORD);
        $this->registry->register($handler);

        // Act
        $retrievedHandler = $this->registry->getHandler(NotificationType::FORGOT_PASSWORD);

        // Assert
        $this->assertSame($handler, $retrievedHandler);
    }

    public function test_get_handler_throws_exception_when_handler_not_registered(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No handler registered for notification type: forgot_password');

        // Act
        $this->registry->getHandler(NotificationType::FORGOT_PASSWORD);
    }

    public function test_has_handler_returns_false_when_handler_not_registered(): void
    {
        // Act & Assert
        $this->assertFalse($this->registry->hasHandler(NotificationType::FORGOT_PASSWORD));
    }

    public function test_has_handler_returns_true_after_registration(): void
    {
        // Arrange
        $handler = $this->createMockHandler(NotificationType::FORGOT_PASSWORD);
        $this->registry->register($handler);

        // Act & Assert
        $this->assertTrue($this->registry->hasHandler(NotificationType::FORGOT_PASSWORD));
    }

    public function test_register_overwrites_existing_handler(): void
    {
        // Arrange
        $firstHandler = $this->createMockHandler(NotificationType::FORGOT_PASSWORD);
        $secondHandler = $this->createMockHandler(NotificationType::FORGOT_PASSWORD);

        // Act
        $this->registry->register($firstHandler);
        $this->registry->register($secondHandler);

        // Assert
        $retrievedHandler = $this->registry->getHandler(NotificationType::FORGOT_PASSWORD);
        $this->assertSame($secondHandler, $retrievedHandler);
        $this->assertNotSame($firstHandler, $retrievedHandler);
    }

    /**
     * Create a mock notification handler.
     */
    private function createMockHandler(NotificationType $type): NotificationHandlerInterface
    {
        $handler = $this->createMock(NotificationHandlerInterface::class);
        $handler->method('getNotificationType')->willReturn($type);

        return $handler;
    }
}
