<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications\Handlers;

use App\Enums\Notification\NotificationType;
use App\Mail\Payment\PaymentSuccessfulMail;
use App\Models\Auth\User;
use App\Models\Payment\Payment;
use App\Services\Notifications\Handlers\PaymentSuccessHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Unit tests for PaymentSuccessHandler.
 */
class PaymentSuccessHandlerTest extends TestCase
{
    use RefreshDatabase;

    private PaymentSuccessHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new PaymentSuccessHandler();
    }

    public function test_get_notification_type_returns_payment_success(): void
    {
        // Act
        $type = $this->handler->getNotificationType();

        // Assert
        $this->assertSame(NotificationType::PAYMENT_SUCCESS, $type);
    }

    public function test_handle_sends_payment_success_email_successfully(): void
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $payment = Payment::factory()->create();
        $parameters = ['payment' => $payment];

        // Act
        $result = $this->handler->handle($user, $parameters);

        // Assert
        $this->assertTrue($result);
        Mail::assertQueued(PaymentSuccessfulMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_handle_throws_exception_when_payment_is_missing(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = [];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: payment');

        // Act
        $this->handler->handle($user, $parameters);
    }

    public function test_handle_throws_exception_when_payment_is_not_payment_instance(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $parameters = ['payment' => 'not-a-payment'];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "payment" must be an instance of Payment');

        // Act
        $this->handler->handle($user, $parameters);
    }
}
