<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Contracts\Notifications\NotificationHandlerInterface;
use App\Contracts\Notifications\NotificationRegistryInterface;
use App\Enums\NotificationType;
use App\Models\Auth\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for NotificationService.
 */
class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;
    private NotificationRegistryInterface $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->createMock(NotificationRegistryInterface::class);
        $this->service = new NotificationService($this->registry);
    }

    public function test_send_successfully_delegates_to_handler(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $type = NotificationType::FORGOT_PASSWORD;
        $parameters = ['token' => 'test-token-123'];

        $handler = $this->createMock(NotificationHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($user, $parameters)
            ->willReturn(true);

        $this->registry->expects($this->once())
            ->method('getHandler')
            ->with($type)
            ->willReturn($handler);

        Log::shouldReceive('info')
            ->once()
            ->with('Processing notification', \Mockery::on(function ($context) use ($type, $user) {
                return $context['type'] === $type->value
                    && $context['receiver_id'] === $user->id
                    && $context['receiver_email'] === $user->email;
            }));

        // Act
        $result = $this->service->send($user, $type, $parameters);

        // Assert
        $this->assertTrue($result);
    }

    public function test_send_logs_and_rethrows_exception_when_handler_not_found(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $type = NotificationType::FORGOT_PASSWORD;
        $parameters = ['token' => 'test-token-123'];

        $exception = new \InvalidArgumentException('No handler registered for notification type: forgot_password');

        $this->registry->expects($this->once())
            ->method('getHandler')
            ->with($type)
            ->willThrowException($exception);

        Log::shouldReceive('info')
            ->once()
            ->with('Processing notification', \Mockery::any());

        Log::shouldReceive('error')
            ->once()
            ->with('Unsupported notification type', \Mockery::on(function ($context) use ($type) {
                return $context['type'] === $type->value
                    && isset($context['error']);
            }));

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No handler registered for notification type: forgot_password');

        // Act
        $this->service->send($user, $type, $parameters);
    }

    public function test_send_logs_and_rethrows_exception_when_handler_fails(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $type = NotificationType::FORGOT_PASSWORD;
        $parameters = ['token' => 'test-token-123'];

        $exception = new \Exception('Failed to send email');

        $handler = $this->createMock(NotificationHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($user, $parameters)
            ->willThrowException($exception);

        $this->registry->expects($this->once())
            ->method('getHandler')
            ->with($type)
            ->willReturn($handler);

        Log::shouldReceive('info')
            ->once()
            ->with('Processing notification', \Mockery::any());

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send notification', \Mockery::on(function ($context) use ($type, $user) {
                return $context['type'] === $type->value
                    && $context['receiver_id'] === $user->id
                    && isset($context['error']);
            }));

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to send email');

        // Act
        $this->service->send($user, $type, $parameters);
    }

    public function test_send_returns_false_when_handler_returns_false(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $type = NotificationType::FORGOT_PASSWORD;
        $parameters = ['token' => 'test-token-123'];

        $handler = $this->createMock(NotificationHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($user, $parameters)
            ->willReturn(false);

        $this->registry->expects($this->once())
            ->method('getHandler')
            ->with($type)
            ->willReturn($handler);

        Log::shouldReceive('info')
            ->once()
            ->with('Processing notification', \Mockery::any());

        // Act
        $result = $this->service->send($user, $type, $parameters);

        // Assert
        $this->assertFalse($result);
    }

    public function test_send_passes_correct_parameters_to_handler(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $type = NotificationType::FORGOT_PASSWORD;
        $parameters = [
            'token' => 'test-token-123',
            'expiration_minutes' => 60,
            'custom_field' => 'custom_value',
        ];

        $handler = $this->createMock(NotificationHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with(
                $this->equalTo($user),
                $this->equalTo($parameters)
            )
            ->willReturn(true);

        $this->registry->expects($this->once())
            ->method('getHandler')
            ->with($type)
            ->willReturn($handler);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->service->send($user, $type, $parameters);

        // Assert
        $this->assertTrue($result);
    }
}
