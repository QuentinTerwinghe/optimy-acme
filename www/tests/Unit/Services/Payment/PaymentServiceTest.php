<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\DTOs\Payment\FakeProcessPaymentDTO;
use App\Enums\Donation\DonationStatus;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Exceptions\Payment\PaymentProcessingException;
use App\Exceptions\Payment\UnsupportedPaymentMethodException;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        // PaymentServiceProvider already registers gateways in bootstrap
        $this->paymentService = app(PaymentService::class);
    }

    public function test_creates_payment_for_donation(): void
    {
        // Arrange
        $donation = Donation::factory()->create(['amount' => 100.00]);

        // Act
        $payment = $this->paymentService->createPayment(
            $donation,
            PaymentMethodEnum::FAKE,
            ['test' => 'metadata']
        );

        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($donation->id, $payment->donation_id);
        $this->assertEquals(PaymentMethodEnum::FAKE, $payment->payment_method);
        $this->assertEquals(PaymentStatusEnum::PENDING, $payment->status);
        $this->assertEquals(100.00, $payment->amount);
        $this->assertEquals('USD', $payment->currency);
        $this->assertNotNull($payment->initiated_at);
        $this->assertArrayHasKey('test', $payment->metadata);
    }

    public function test_processes_payment_successfully(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'amount' => 50.00,
            'status' => DonationStatus::PENDING,
        ]);
        $payment = Payment::factory()->pending()->create([
            'donation_id' => $donation->id,
            'amount' => 50.00,
        ]);

        // Act
        $result = $this->paymentService->processPayment($payment, new FakeProcessPaymentDTO());

        // Assert
        $this->assertEquals(PaymentStatusEnum::COMPLETED, $result->status);
        $this->assertNotNull($result->transaction_id);
        $this->assertNotNull($result->completed_at);

        // Verify donation was updated
        $donation->refresh();
        $this->assertEquals(DonationStatus::SUCCESS, $donation->status);
    }

    public function test_processes_payment_with_failure(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->pending()->create([
            'donation_id' => $donation->id,
        ]);

        $paymentDto = new FakeProcessPaymentDTO(
            simulateFailure: true,
            errorMessage: 'Payment declined',
        );

        // Assert
        $this->expectException(PaymentProcessingException::class);

        // Act
        $this->paymentService->processPayment($payment, $paymentDto);
    }

    public function test_cannot_process_completed_payment(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->completed()->create([
            'donation_id' => $donation->id,
        ]);

        // Assert
        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('terminal state');

        // Act
        $this->paymentService->processPayment($payment, new FakeProcessPaymentDTO());
    }

    public function test_throws_exception_for_unsupported_payment_method(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::PAYPAL, // Not implemented yet
            'status' => PaymentStatusEnum::PENDING,
        ]);

        // Assert
        $this->expectException(UnsupportedPaymentMethodException::class);

        // Act
        $this->paymentService->processPayment($payment, new FakeProcessPaymentDTO());
    }

    public function test_refunds_payment_successfully(): void
    {
        // Arrange
        $donation = Donation::factory()->create(['status' => DonationStatus::SUCCESS]);
        $payment = Payment::factory()->completed()->create([
            'donation_id' => $donation->id,
            'amount' => 100.00,
        ]);

        // Act
        $result = $this->paymentService->refundPayment($payment);

        // Assert
        $this->assertEquals(PaymentStatusEnum::REFUNDED, $result->status);
        $this->assertNotNull($result->refunded_at);

        // Verify donation was updated
        $donation->refresh();
        $this->assertEquals(DonationStatus::FAILED, $donation->status);
    }

    public function test_refunds_partial_payment_amount(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->completed()->create([
            'donation_id' => $donation->id,
            'amount' => 100.00,
        ]);

        // Act
        $result = $this->paymentService->refundPayment($payment, 50.00);

        // Assert
        $this->assertEquals(PaymentStatusEnum::REFUNDED, $result->status);
    }

    public function test_verifies_payment_status(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->completed()->create([
            'donation_id' => $donation->id,
        ]);

        // Act
        $result = $this->paymentService->verifyPaymentStatus($payment);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertNotNull($result->metadata);
        $this->assertArrayHasKey('last_verified_at', $result->metadata);
    }

    public function test_get_available_payment_methods(): void
    {
        // Act
        $methods = $this->paymentService->getAvailablePaymentMethods();

        // Assert
        $this->assertIsArray($methods);
        $this->assertContains(PaymentMethodEnum::FAKE, $methods);
        $this->assertCount(1, $methods); // Only FAKE is enabled
    }

    public function test_get_enabled_payment_methods_for_display(): void
    {
        // Act
        $methods = $this->paymentService->getEnabledPaymentMethodsForDisplay();

        // Assert
        $this->assertIsArray($methods);
        $this->assertNotEmpty($methods);

        // Check the structure of each method
        foreach ($methods as $method) {
            $this->assertArrayHasKey('value', $method);
            $this->assertArrayHasKey('label', $method);
            $this->assertArrayHasKey('isTest', $method);
        }

        // Verify at least the fake payment method is present
        $values = array_column($methods, 'value');
        $this->assertContains('fake', $values);

        // Find the fake method and verify its properties
        $fakeMethod = collect($methods)->firstWhere('value', 'fake');
        $this->assertNotNull($fakeMethod);
        $this->assertEquals('Fake Payment (Test)', $fakeMethod['label']);
        $this->assertTrue($fakeMethod['isTest']);
    }

    public function test_get_payment_statistics(): void
    {
        // Arrange
        $donation = Donation::factory()->create();

        // Create various payments
        Payment::factory()->completed()->create(['donation_id' => $donation->id, 'amount' => 100.00]);
        Payment::factory()->completed()->create(['donation_id' => $donation->id, 'amount' => 50.00]);
        Payment::factory()->failed()->create(['donation_id' => $donation->id, 'amount' => 25.00]);
        Payment::factory()->pending()->create(['donation_id' => $donation->id, 'amount' => 75.00]);
        Payment::factory()->refunded()->create(['donation_id' => $donation->id, 'amount' => 30.00]);

        // Act
        $stats = $this->paymentService->getPaymentStatistics($donation);

        // Assert
        $this->assertEquals(5, $stats['total_payments']);
        $this->assertEquals(2, $stats['successful_payments']);
        $this->assertEquals(1, $stats['failed_payments']);
        $this->assertEquals(1, $stats['pending_payments']);
        $this->assertEquals(1, $stats['refunded_payments']);
        $this->assertEquals(150.00, $stats['total_amount_paid']);
        $this->assertEquals(30.00, $stats['total_amount_refunded']);
    }
}
