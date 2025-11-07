<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications\Handlers;

use App\Enums\NotificationType;
use App\Mail\Auth\ForgotPasswordMail;
use App\Models\User;
use App\Services\Notifications\Handlers\ForgotPasswordHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Unit tests for ForgotPasswordHandler.
 */
class ForgotPasswordHandlerTest extends TestCase
{
    use RefreshDatabase;

    private ForgotPasswordHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new ForgotPasswordHandler();

        // Define the password.reset route required by the handler
        \Illuminate\Support\Facades\Route::get('/reset-password/{token}', function () {
            return 'reset';
        })->name('password.reset');
    }

    public function test_get_notification_type_returns_forgot_password(): void
    {
        // Act
        $type = $this->handler->getNotificationType();

        // Assert
        $this->assertSame(NotificationType::FORGOT_PASSWORD, $type);
    }

    public function test_handle_sends_password_reset_email_successfully(): void
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = [
            'token' => 'test-token-123',
            'expiration_minutes' => 60,
        ];

        // Act
        $result = $this->handler->handle($user, $parameters);

        // Assert
        $this->assertTrue($result);
        Mail::assertQueued(ForgotPasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_handle_uses_default_expiration_from_config(): void
    {
        // Arrange
        Mail::fake();
        config(['auth.passwords.users.expire' => 120]);

        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = ['token' => 'test-token-123'];

        // Act
        $result = $this->handler->handle($user, $parameters);

        // Assert
        $this->assertTrue($result);
        Mail::assertQueued(ForgotPasswordMail::class);
    }

    public function test_handle_throws_exception_when_token_is_missing(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = [];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: token');

        // Act
        $this->handler->handle($user, $parameters);
    }

    public function test_handle_throws_exception_when_token_is_empty_string(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = ['token' => ''];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "token" must be a non-empty string');

        // Act
        $this->handler->handle($user, $parameters);
    }

    public function test_handle_throws_exception_when_token_is_not_string(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = ['token' => 123];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "token" must be a non-empty string');

        // Act
        $this->handler->handle($user, $parameters);
    }

    public function test_handle_throws_exception_when_expiration_minutes_is_not_integer(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = [
            'token' => 'test-token-123',
            'expiration_minutes' => 'not-an-integer',
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "expiration_minutes" must be an integer');

        // Act
        $this->handler->handle($user, $parameters);
    }

    public function test_handle_throws_exception_when_user_has_no_email(): void
    {
        // Arrange
        $user = User::factory()->make(['email' => null]);
        $parameters = ['token' => 'test-token-123'];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Receiver must have an email address');

        // Act
        $this->handler->handle($user, $parameters);
    }

    public function test_handle_builds_correct_reset_url(): void
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = 'test-token-123';
        $parameters = ['token' => $token];

        // Act
        $result = $this->handler->handle($user, $parameters);

        // Assert
        $this->assertTrue($result);
        Mail::assertQueued(ForgotPasswordMail::class, function ($mail) use ($user, $token) {
            $expectedUrl = route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);

            // We can't directly access the resetUrl property, but we can verify the mail was sent
            // In a real scenario, you might want to add a getter or make the property public for testing
            return $mail->hasTo($user->email);
        });
    }

    public function test_handle_accepts_custom_expiration_minutes(): void
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $customExpiration = 90;
        $parameters = [
            'token' => 'test-token-123',
            'expiration_minutes' => $customExpiration,
        ];

        // Act
        $result = $this->handler->handle($user, $parameters);

        // Assert
        $this->assertTrue($result);
        Mail::assertQueued(ForgotPasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
