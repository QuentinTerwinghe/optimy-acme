<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\Contracts\Payment\PaymentMethodHandlerInterface;
use App\DTOs\Payment\PaymentPreparationResultDTO;
use App\Enums\Payment\PaymentMethodEnum;
use App\Models\Payment\Payment;
use App\Services\Payment\PaymentPreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for PaymentPreparationService
 */
class PaymentPreparationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentPreparationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentPreparationService();
    }

    public function test_register_handler(): void
    {
        // Arrange
        $handler = $this->createMock(PaymentMethodHandlerInterface::class);

        // Act
        $this->service->registerHandler(PaymentMethodEnum::FAKE->value, $handler);

        // Assert - If we can prepare without exception, handler was registered
        $payment = Payment::factory()->fake()->create();

        $handler->expects($this->once())
            ->method('prepare')
            ->with($payment)
            ->willReturn(new PaymentPreparationResultDTO(
                payload: ['test' => 'data'],
                redirectUrl: 'https://test.com'
            ));

        $redirectUrl = $this->service->prepare($payment);
        $this->assertEquals('https://test.com', $redirectUrl);
    }

    public function test_prepare_calls_handler_and_updates_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->fake()->create();

        $mockHandler = $this->createMock(PaymentMethodHandlerInterface::class);
        $expectedPayload = [
            'session_id' => 'TEST_SESSION_123',
            'gateway' => 'test',
        ];
        $expectedRedirectUrl = 'https://test-gateway.com/checkout/123';

        $mockHandler->expects($this->once())
            ->method('prepare')
            ->with($payment)
            ->willReturn(new PaymentPreparationResultDTO(
                payload: $expectedPayload,
                redirectUrl: $expectedRedirectUrl
            ));

        $this->service->registerHandler(PaymentMethodEnum::FAKE->value, $mockHandler);

        // Act
        $redirectUrl = $this->service->prepare($payment);

        // Assert
        $this->assertEquals($expectedRedirectUrl, $redirectUrl);

        // Refresh payment from database
        $payment->refresh();
        $this->assertEquals($expectedPayload, $payment->payload);
        $this->assertEquals($expectedRedirectUrl, $payment->redirect_url);
        $this->assertNotNull($payment->prepared_at);
    }

    public function test_prepare_throws_exception_for_unregistered_handler(): void
    {
        // Arrange
        $payment = Payment::factory()->fake()->create();

        // Don't register any handler

        // Assert & Act
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No handler registered for payment method: fake');

        $this->service->prepare($payment);
    }

    public function test_multiple_handlers_can_be_registered(): void
    {
        // Arrange
        $fakeHandler = $this->createMock(PaymentMethodHandlerInterface::class);
        $paypalHandler = $this->createMock(PaymentMethodHandlerInterface::class);

        $fakePayment = Payment::factory()->fake()->create();
        $paypalPayment = Payment::factory()->paypal()->create();

        // Configure handlers
        $fakeHandler->expects($this->once())
            ->method('prepare')
            ->with($fakePayment)
            ->willReturn(new PaymentPreparationResultDTO(
                payload: ['fake' => 'data'],
                redirectUrl: 'https://fake.com'
            ));

        $paypalHandler->expects($this->once())
            ->method('prepare')
            ->with($paypalPayment)
            ->willReturn(new PaymentPreparationResultDTO(
                payload: ['paypal' => 'data'],
                redirectUrl: 'https://paypal.com'
            ));

        // Act
        $this->service->registerHandler(PaymentMethodEnum::FAKE->value, $fakeHandler);
        $this->service->registerHandler(PaymentMethodEnum::PAYPAL->value, $paypalHandler);

        $fakeRedirectUrl = $this->service->prepare($fakePayment);
        $paypalRedirectUrl = $this->service->prepare($paypalPayment);

        // Assert
        $this->assertEquals('https://fake.com', $fakeRedirectUrl);
        $this->assertEquals('https://paypal.com', $paypalRedirectUrl);
    }
}
