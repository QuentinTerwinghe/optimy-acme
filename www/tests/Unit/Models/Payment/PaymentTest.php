<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_belongs_to_donation(): void
    {
        // Arrange
        $donation = Donation::factory()->create();
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
        ]);

        // Act
        $result = $payment->donation;

        // Assert
        $this->assertInstanceOf(Donation::class, $result);
        $this->assertEquals($donation->id, $result->id);
    }

    public function test_is_completed_returns_true_for_completed_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->completed()->create();

        // Act & Assert
        $this->assertTrue($payment->isCompleted());
        $this->assertFalse($payment->isFailed());
        $this->assertFalse($payment->isPending());
        $this->assertFalse($payment->isProcessing());
        $this->assertFalse($payment->isRefunded());
    }

    public function test_is_failed_returns_true_for_failed_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->failed()->create();

        // Act & Assert
        $this->assertTrue($payment->isFailed());
        $this->assertFalse($payment->isCompleted());
        $this->assertFalse($payment->isPending());
        $this->assertFalse($payment->isProcessing());
        $this->assertFalse($payment->isRefunded());
    }

    public function test_is_pending_returns_true_for_pending_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->pending()->create();

        // Act & Assert
        $this->assertTrue($payment->isPending());
        $this->assertFalse($payment->isCompleted());
        $this->assertFalse($payment->isFailed());
        $this->assertFalse($payment->isProcessing());
        $this->assertFalse($payment->isRefunded());
    }

    public function test_is_processing_returns_true_for_processing_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->processing()->create();

        // Act & Assert
        $this->assertTrue($payment->isProcessing());
        $this->assertFalse($payment->isCompleted());
        $this->assertFalse($payment->isFailed());
        $this->assertFalse($payment->isPending());
        $this->assertFalse($payment->isRefunded());
    }

    public function test_is_refunded_returns_true_for_refunded_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->refunded()->create();

        // Act & Assert
        $this->assertTrue($payment->isRefunded());
        $this->assertFalse($payment->isCompleted());
        $this->assertFalse($payment->isFailed());
        $this->assertFalse($payment->isPending());
        $this->assertFalse($payment->isProcessing());
    }

    public function test_mark_as_completed_updates_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->pending()->create();
        $transactionId = 'FAKE_TRANSACTION_123';
        $gatewayResponse = ['success' => true];

        // Act
        $payment->markAsCompleted($transactionId, $gatewayResponse);

        // Assert
        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::COMPLETED, $payment->status);
        $this->assertEquals($transactionId, $payment->transaction_id);
        $this->assertNotNull($payment->completed_at);
        $this->assertNotNull($payment->gateway_response);
    }

    public function test_mark_as_failed_updates_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->processing()->create();
        $errorMessage = 'Payment failed due to insufficient funds';
        $errorCode = 'INSUFFICIENT_FUNDS';
        $gatewayResponse = ['error' => true];

        // Act
        $payment->markAsFailed($errorMessage, $errorCode, $gatewayResponse);

        // Assert
        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::FAILED, $payment->status);
        $this->assertEquals($errorMessage, $payment->error_message);
        $this->assertEquals($errorCode, $payment->error_code);
        $this->assertNotNull($payment->failed_at);
        $this->assertNotNull($payment->gateway_response);
    }

    public function test_mark_as_processing_updates_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->pending()->create();

        // Act
        $payment->markAsProcessing();

        // Assert
        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::PROCESSING, $payment->status);
    }

    public function test_mark_as_refunded_updates_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->completed()->create();
        $refundTransactionId = 'REFUND_TRANSACTION_123';

        // Act
        $payment->markAsRefunded($refundTransactionId);

        // Assert
        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::REFUNDED, $payment->status);
        $this->assertEquals($refundTransactionId, $payment->transaction_id);
        $this->assertNotNull($payment->refunded_at);
    }

    public function test_mark_as_refunded_keeps_original_transaction_id_if_not_provided(): void
    {
        // Arrange
        $originalTransactionId = 'ORIGINAL_TRANSACTION_123';
        $payment = Payment::factory()->completed()->create([
            'transaction_id' => $originalTransactionId,
        ]);

        // Act
        $payment->markAsRefunded();

        // Assert
        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::REFUNDED, $payment->status);
        $this->assertEquals($originalTransactionId, $payment->transaction_id);
        $this->assertNotNull($payment->refunded_at);
    }

    public function test_payment_casts_attributes_correctly(): void
    {
        // Arrange
        $payment = Payment::factory()->completed()->create([
            'amount' => 123.45,
            'metadata' => ['key' => 'value'],
        ]);

        // Act
        $payment->refresh();

        // Assert
        $this->assertIsFloat($payment->amount);
        $this->assertEquals(123.45, $payment->amount);
        $this->assertIsArray($payment->metadata);
        $this->assertEquals(['key' => 'value'], $payment->metadata);
        $this->assertInstanceOf(PaymentMethodEnum::class, $payment->payment_method);
        $this->assertInstanceOf(PaymentStatusEnum::class, $payment->status);
        $this->assertInstanceOf(\DateTime::class, $payment->created_at);
    }

    public function test_payment_uses_uuid_as_primary_key(): void
    {
        // Arrange
        $payment = Payment::factory()->create();

        // Assert
        $this->assertIsString($payment->id);
        $this->assertEquals(36, strlen($payment->id)); // UUID is 36 characters with dashes
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $payment->id
        );
    }

    public function test_payment_soft_deletes(): void
    {
        // Arrange
        $payment = Payment::factory()->create();
        $paymentId = $payment->id;

        // Act
        $payment->delete();

        // Assert
        $this->assertSoftDeleted('payments', ['id' => $paymentId]);

        // Verify can still access with trashed
        $deletedPayment = Payment::withTrashed()->find($paymentId);
        $this->assertNotNull($deletedPayment);
        $this->assertNotNull($deletedPayment->deleted_at);
    }
}
