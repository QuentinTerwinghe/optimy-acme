<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Enums\NotificationType;
use App\Models\Auth\User;
use App\Services\Notifications\AbstractNotificationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for AbstractNotificationHandler.
 */
class AbstractNotificationHandlerTest extends TestCase
{
    use RefreshDatabase;

    private ConcreteTestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new ConcreteTestHandler();
    }

    public function test_handle_validates_receiver_successfully(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = ['test' => 'value'];

        // Act
        $result = $this->handler->handle($user, $parameters);

        // Assert
        $this->assertTrue($result);
    }

    public function test_handle_throws_exception_when_receiver_has_no_email(): void
    {
        // Arrange
        $user = User::factory()->make(['email' => null]);
        $parameters = ['test' => 'value'];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Receiver must have an email address');

        // Act
        $this->handler->handle($user, $parameters);
    }

    public function test_handle_logs_success_when_send_returns_true(): void
    {
        // Arrange
        Log::spy();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = ['test' => 'value'];
        $this->handler->setShouldSucceed(true);

        // Act
        $result = $this->handler->handle($user, $parameters);

        // Assert
        $this->assertTrue($result);
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Notification sent successfully', \Mockery::on(function ($context) use ($user) {
                return $context['type'] === 'forgot_password'
                    && $context['receiver_id'] === $user->id
                    && $context['receiver_email'] === $user->email;
            }));
    }

    public function test_handle_logs_failure_when_send_returns_false(): void
    {
        // Arrange
        Log::spy();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = ['test' => 'value'];
        $this->handler->setShouldSucceed(false);

        // Act
        $result = $this->handler->handle($user, $parameters);

        // Assert
        $this->assertFalse($result);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Notification sending failed', \Mockery::on(function ($context) use ($user) {
                return $context['type'] === 'forgot_password'
                    && $context['receiver_id'] === $user->id
                    && $context['receiver_email'] === $user->email;
            }));
    }

    public function test_handle_logs_error_and_rethrows_exception(): void
    {
        // Arrange
        $exception = new \Exception('Test exception');
        Log::spy();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = ['test' => 'value'];
        $this->handler->setShouldThrowException($exception);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        // Act
        try {
            $this->handler->handle($user, $parameters);
        } catch (\Exception $e) {
            // Verify the log was called
            Log::shouldHaveReceived('error')
                ->once()
                ->with('Notification sending error', \Mockery::on(function ($context) use ($user) {
                    return $context['type'] === 'forgot_password'
                        && $context['receiver_id'] === $user->id
                        && $context['receiver_email'] === $user->email
                        && $context['error'] === 'Test exception'
                        && isset($context['trace']);
                }));
            throw $e;
        }
    }

    public function test_get_notification_type_returns_correct_type(): void
    {
        // Act
        $type = $this->handler->getNotificationType();

        // Assert
        $this->assertInstanceOf(NotificationType::class, $type);
    }
}

/**
 * Concrete implementation of AbstractNotificationHandler for testing.
 */
class ConcreteTestHandler extends AbstractNotificationHandler
{
    private bool $shouldSucceed = true;
    private ?\Exception $exceptionToThrow = null;

    public function getNotificationType(): NotificationType
    {
        return NotificationType::FORGOT_PASSWORD;
    }

    protected function send(User $receiver, array $parameters): bool
    {
        if ($this->exceptionToThrow !== null) {
            throw $this->exceptionToThrow;
        }

        return $this->shouldSucceed;
    }

    public function setShouldSucceed(bool $shouldSucceed): void
    {
        $this->shouldSucceed = $shouldSucceed;
    }

    public function setShouldThrowException(\Exception $exception): void
    {
        $this->exceptionToThrow = $exception;
    }
}
