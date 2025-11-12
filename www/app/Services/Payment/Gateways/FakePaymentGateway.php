<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\Payment\ProcessPaymentDTOInterface;
use App\DTOs\Payment\FakeProcessPaymentDTO;
use App\DTOs\Payment\RefundPaymentDTO;
use App\Enums\Payment\PaymentMethodEnum;
use App\Exceptions\Payment\PaymentProcessingException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Models\Payment\Payment;
use App\Services\Payment\AbstractPaymentGateway;
use Illuminate\Support\Str;

/**
 * Fake payment gateway for testing purposes.
 * Simulates payment processing without actual financial transactions.
 */
class FakePaymentGateway extends AbstractPaymentGateway
{
    /**
     * Process a payment through the fake gateway.
     *
     * @param Payment $payment The payment to process
     * @param FakeProcessPaymentDTO $dto Payment processing data
     * @return Payment The updated payment with transaction details
     * @throws PaymentProcessingException
     */
    public function processPayment(Payment $payment, ProcessPaymentDTOInterface $dto): Payment
    {
        try {
            if (!$dto instanceof FakeProcessPaymentDTO) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'FakePaymentGateway requires %s, %s given',
                        FakeProcessPaymentDTO::class,
                        get_class($dto)
                    )
                );
            }

            $this->log('Starting payment processing', $payment, [
                'simulate_failure' => $dto->simulateFailure,
            ]);

            // Mark as processing
            $payment->markAsProcessing();

            // Simulate processing delay if specified
            if ($dto->processingDelay > 0) {
                sleep($dto->processingDelay);
            }

            if ($dto->simulateFailure) {
                // Simulate a failed payment
                $errorMessage = $dto->errorMessage ?? 'Simulated payment failure';
                $errorCode = $dto->errorCode ?? 'FAKE_ERROR';

                $payment->markAsFailed($errorMessage, $errorCode, [
                    'gateway' => $this->getName(),
                    'simulated' => true,
                    'timestamp' => now()->toISOString(),
                ]);

                throw PaymentProcessingException::forPayment(
                    (string) $payment->id,
                    $errorMessage
                );
            }

            // Generate a fake transaction ID
            $transactionId = 'FAKE_' . strtoupper(Str::random(16));

            // Mark as completed
            $payment->markAsCompleted($transactionId, [
                'gateway' => $this->getName(),
                'simulated' => true,
                'timestamp' => now()->toISOString(),
                'card_last_four' => $dto->cardLastFour ?? '4242',
                'card_brand' => $dto->cardBrand ?? 'Visa',
            ]);

            $this->log('Payment processed successfully', $payment, [
                'transaction_id' => $transactionId,
            ]);

            return $payment->fresh() ?? $payment;
        } catch (\Exception $e) {
            $payment->markAsFailed($payment->error_message ?? $e->getMessage(), $payment->error_code ?? 'PROCESSING_ERROR');
            $this->log('Payment processing failed', $payment, [
                'error' => $e->getMessage(),
            ]);
            throw PaymentProcessingException::forPayment((string) $payment->id, $e->getMessage());
        }
    }

    /**
     * Perform the actual refund operation.
     *
     * @param Payment $payment The payment to refund
     * @param RefundPaymentDTO $dto Refund data
     * @return Payment The updated payment with refund details
     */
    protected function performRefund(Payment $payment, RefundPaymentDTO $dto): Payment
    {
        $refundAmount = $this->getRefundAmount($payment, $dto);

        $this->log('Processing refund', $payment, [
            'refund_amount' => $refundAmount,
            'reason' => $dto->reason,
        ]);

        // Generate a fake refund transaction ID
        $refundTransactionId = 'REFUND_' . strtoupper(Str::random(16));

        // Mark as refunded
        $payment->markAsRefunded($refundTransactionId);

        // Update gateway response with refund details
        $payment->update([
            'gateway_response' => json_encode([
                'gateway' => $this->getName(),
                'simulated' => true,
                'refund_amount' => $refundAmount,
                'original_amount' => $payment->amount,
                'refund_transaction_id' => $refundTransactionId,
                'reason' => $dto->reason,
                'timestamp' => now()->toISOString(),
            ]),
        ]);

        $this->log('Refund processed successfully', $payment, [
            'refund_transaction_id' => $refundTransactionId,
        ]);

        return $payment->fresh() ?? $payment;
    }

    /**
     * Verify the status of a payment with the gateway.
     *
     * @param Payment $payment The payment to verify
     * @return Payment The updated payment with current status
     * @throws PaymentVerificationException
     */
    public function verifyPaymentStatus(Payment $payment): Payment
    {
        // In fake gateway, we just return the current status
        // In real implementations, this would check with the actual gateway
        if (!$payment->transaction_id) {
            throw PaymentVerificationException::forPayment(
                (string) $payment->id,
                'No transaction ID found for verification'
            );
        }

        $this->log('Verifying payment status', $payment);

        // Simulate verification
        $payment->update([
            'metadata' => array_merge($payment->metadata ?? [], [
                'last_verified_at' => now()->toISOString(),
                'verification_method' => 'fake_gateway',
            ]),
        ]);

        return $payment->fresh() ?? $payment;
    }

    /**
     * Get the payment method this gateway handles.
     *
     * @return PaymentMethodEnum
     */
    public function getPaymentMethod(): PaymentMethodEnum
    {
        return PaymentMethodEnum::FAKE;
    }

    /**
     * Check if this gateway can handle the given payment method.
     *
     * @param string $paymentMethod The payment method to check
     * @return bool
     */
    public function supports(string $paymentMethod): bool
    {
        return $paymentMethod === PaymentMethodEnum::FAKE->value;
    }

    /**
     * Get the name of this payment gateway.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'fake';
    }
}
