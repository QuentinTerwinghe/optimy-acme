<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\DTOs\Payment\ProcessPaymentDTO;
use App\DTOs\Payment\RefundPaymentDTO;
use App\Exceptions\Payment\PaymentRefundException;
use App\Models\Payment\Payment;

/**
 * Abstract base class for payment gateway implementations.
 * Provides common functionality and enforces the gateway contract.
 */
abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    /**
     * Process a payment through the gateway.
     * Must be implemented by concrete gateways.
     *
     * @param Payment $payment
     * @param ProcessPaymentDTO $dto
     * @return Payment
     */
    abstract public function processPayment(Payment $payment, ProcessPaymentDTO $dto): Payment;

    /**
     * Refund a completed payment.
     * Provides base validation, concrete implementation in subclasses.
     *
     * @param Payment $payment
     * @param RefundPaymentDTO $dto
     * @return Payment
     * @throws PaymentRefundException
     */
    public function refundPayment(Payment $payment, RefundPaymentDTO $dto): Payment
    {
        // Base validation
        $this->validateRefund($payment, $dto);

        // Concrete implementation in subclasses
        return $this->performRefund($payment, $dto);
    }

    /**
     * Perform the actual refund operation.
     * Must be implemented by concrete gateways.
     *
     * @param Payment $payment
     * @param RefundPaymentDTO $dto
     * @return Payment
     */
    abstract protected function performRefund(Payment $payment, RefundPaymentDTO $dto): Payment;

    /**
     * Verify the status of a payment with the gateway.
     * Must be implemented by concrete gateways.
     *
     * @param Payment $payment
     * @return Payment
     */
    abstract public function verifyPaymentStatus(Payment $payment): Payment;

    /**
     * Get the name of this payment gateway.
     * Must be implemented by concrete gateways.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Get the payment method this gateway handles.
     * Must be implemented by concrete gateways.
     *
     * @return \App\Enums\Payment\PaymentMethodEnum
     */
    abstract public function getPaymentMethod(): \App\Enums\Payment\PaymentMethodEnum;

    /**
     * Check if this gateway supports the given payment method.
     * Must be implemented by concrete gateways.
     *
     * @param string $paymentMethod
     * @return bool
     */
    abstract public function supports(string $paymentMethod): bool;

    /**
     * Validate that a payment can be refunded.
     *
     * @param Payment $payment
     * @param RefundPaymentDTO $dto
     * @return void
     * @throws PaymentRefundException
     */
    protected function validateRefund(Payment $payment, RefundPaymentDTO $dto): void
    {
        // Verify payment can be refunded
        if (!$payment->isCompleted()) {
            throw PaymentRefundException::notRefundable(
                (string) $payment->id,
                $payment->status->value
            );
        }

        // Validate refund amount if specified
        if ($dto->amount !== null) {
            $refundAmount = $dto->amount;
            if ($refundAmount > (float) $payment->amount) {
                throw PaymentRefundException::invalidAmount(
                    $refundAmount,
                    (float) $payment->amount
                );
            }

            if ($refundAmount <= 0) {
                throw PaymentRefundException::invalidAmount(
                    $refundAmount,
                    (float) $payment->amount
                );
            }
        }
    }

    /**
     * Get the refund amount (full refund if not specified in DTO).
     *
     * @param Payment $payment
     * @param RefundPaymentDTO $dto
     * @return float
     */
    protected function getRefundAmount(Payment $payment, RefundPaymentDTO $dto): float
    {
        return $dto->amount ?? (float) $payment->amount;
    }

    /**
     * Log gateway operation.
     *
     * @param string $operation
     * @param Payment $payment
     * @param array<string, mixed> $context
     * @return void
     */
    protected function log(string $operation, Payment $payment, array $context = []): void
    {
        \Illuminate\Support\Facades\Log::info(
            sprintf('[%s] %s', $this->getName(), $operation),
            array_merge([
                'payment_id' => $payment->id,
                'donation_id' => $payment->donation_id,
                'amount' => $payment->amount,
            ], $context)
        );
    }
}
