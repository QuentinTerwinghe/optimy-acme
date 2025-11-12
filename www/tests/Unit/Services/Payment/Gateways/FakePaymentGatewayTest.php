<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment\Gateways;

use App\DTOs\Payment\FakeProcessPaymentDTO;
use App\DTOs\Payment\RefundPaymentDTO;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Exceptions\Payment\PaymentProcessingException;
use App\Exceptions\Payment\PaymentRefundException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use App\Services\Payment\Gateways\FakePaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakePaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakePaymentGateway();
    }

    public function test_gateway_name_is_fake(): void
    {
        // Assert
        $this->assertEquals('fake', $this->gateway->getName());
    }

    public function test_get_payment_method_returns_fake(): void
    {
        // Assert
        $this->assertEquals(PaymentMethodEnum::FAKE, $this->gateway->getPaymentMethod());
    }

    public function test_supports_fake_payment_method(): void
    {
        // Assert
        $this->assertTrue($this->gateway->supports(PaymentMethodEnum::FAKE->value));
        $this->assertFalse($this->gateway->supports(PaymentMethodEnum::PAYPAL->value));
        $this->assertFalse($this->gateway->supports(PaymentMethodEnum::CREDIT_CARD->value));
    }

    public function test_processes_payment_successfully(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $dto = new FakeProcessPaymentDTO();

        // Act
        $result = $this->gateway->processPayment($payment, $dto);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(PaymentStatusEnum::COMPLETED, $result->status);
        $this->assertNotNull($result->transaction_id);
        $this->assertStringStartsWith('FAKE_', $result->transaction_id);
        $this->assertNotNull($result->completed_at);
    }

    public function test_processes_payment_with_simulated_failure(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $dto = new FakeProcessPaymentDTO(
            simulateFailure: true,
            errorMessage: 'Test error message',
            errorCode: 'TEST_ERROR'
        );

        // Assert
        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('Test error message');

        // Act
        try {
            $this->gateway->processPayment($payment, $dto);
        } catch (PaymentProcessingException $e) {
            // Verify payment was marked as failed
            $payment->refresh();
            $this->assertEquals(PaymentStatusEnum::FAILED, $payment->status);
            $this->assertEquals('Test error message', $payment->error_message);
            $this->assertEquals('TEST_ERROR', $payment->error_code);
            $this->assertNotNull($payment->failed_at);

            throw $e;
        }
    }

    public function test_refunds_completed_payment_successfully(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::COMPLETED,
            'transaction_id' => 'FAKE_TRANSACTION_123',
        ]);

        $dto = new RefundPaymentDTO();

        // Act
        $result = $this->gateway->refundPayment($payment, $dto);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(PaymentStatusEnum::REFUNDED, $result->status);
        $this->assertStringStartsWith('REFUND_', $result->transaction_id);
        $this->assertNotNull($result->refunded_at);
    }

    public function test_refunds_partial_amount(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::COMPLETED,
            'amount' => 100.00,
            'transaction_id' => 'FAKE_TRANSACTION_123',
        ]);

        $dto = new RefundPaymentDTO(amount: 50.00);

        // Act
        $result = $this->gateway->refundPayment($payment, $dto);

        // Assert
        $this->assertEquals(PaymentStatusEnum::REFUNDED, $result->status);
    }

    public function test_throws_exception_when_refunding_non_completed_payment(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $dto = new RefundPaymentDTO();

        // Assert
        $this->expectException(PaymentRefundException::class);
        $this->expectExceptionMessage('cannot be refunded');

        // Act
        $this->gateway->refundPayment($payment, $dto);
    }

    public function test_throws_exception_when_refund_amount_exceeds_payment(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::COMPLETED,
            'amount' => 100.00,
            'transaction_id' => 'FAKE_TRANSACTION_123',
        ]);

        $dto = new RefundPaymentDTO(amount: 150.00);

        // Assert
        $this->expectException(PaymentRefundException::class);
        $this->expectExceptionMessage('exceeds payment amount');

        // Act
        $this->gateway->refundPayment($payment, $dto);
    }

    public function test_verifies_payment_status_successfully(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::COMPLETED,
            'transaction_id' => 'FAKE_TRANSACTION_123',
        ]);

        // Act
        $result = $this->gateway->verifyPaymentStatus($payment);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertNotNull($result->metadata);
        $this->assertArrayHasKey('last_verified_at', $result->metadata);
    }

    public function test_throws_exception_when_verifying_payment_without_transaction_id(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
            'transaction_id' => null,
        ]);

        // Assert
        $this->expectException(PaymentVerificationException::class);
        $this->expectExceptionMessage('No transaction ID found');

        // Act
        $this->gateway->verifyPaymentStatus($payment);
    }

    public function test_prepare_generates_payload_and_redirect_url(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        // Act
        $result = $this->gateway->prepare($payment);

        // Assert - Payload validation
        $this->assertNotNull($result->payload);
        $this->assertNotNull($result->redirectUrl);
        $this->assertArrayHasKey('session_id', $result->payload);
        $this->assertArrayHasKey('payment_id', $result->payload);
        $this->assertArrayHasKey('amount', $result->payload);
        $this->assertArrayHasKey('currency', $result->payload);
        $this->assertArrayHasKey('callback_url', $result->payload);
        $this->assertArrayHasKey('gateway', $result->payload);
        $this->assertEquals('fake', $result->payload['gateway']);

        // Assert - Callback URL is in payload
        $this->assertStringContainsString('/payment/callback/', $result->payload['callback_url']);
        $this->assertStringContainsString((string) $payment->id, $result->payload['callback_url']);

        // Assert - Redirect URL structure
        $this->assertStringContainsString('/payment/fake/', $result->redirectUrl);

        // Assert - Callback URL is also sent as query parameter in the redirect URL (for external service)
        $this->assertStringContainsString('callback_url=', $result->redirectUrl);
        $parsedUrl = parse_url($result->redirectUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $this->assertArrayHasKey('callback_url', $queryParams);
        $this->assertStringContainsString('/payment/callback/', $queryParams['callback_url']);
    }
}
