<?php

namespace App\Contracts\Payment;

use App\DTOs\Payment\RefundPaymentDTO;
use App\Models\Payment\Payment;

/**
 * Interface for payment gateway implementations.
 * This follows the Strategy pattern to allow different payment methods.
 */
interface PaymentGatewayInterface
{
    /**
     * Process a payment through the gateway.
     *
     * @param Payment $payment The payment to process
     * @param ProcessPaymentDTOInterface $dto Payment processing data
     * @return Payment The updated payment with transaction details
     * @throws \App\Exceptions\Payment\PaymentProcessingException
     */
    public function processPayment(Payment $payment, ProcessPaymentDTOInterface $dto): Payment;

    /**
     * Refund a completed payment.
     *
     * @param Payment $payment The payment to refund
     * @param RefundPaymentDTO $dto Refund data
     * @return Payment The updated payment with refund details
     * @throws \App\Exceptions\Payment\PaymentRefundException
     */
    public function refundPayment(Payment $payment, RefundPaymentDTO $dto): Payment;

    /**
     * Verify the status of a payment with the gateway.
     *
     * @param Payment $payment The payment to verify
     * @return Payment The updated payment with current status
     * @throws \App\Exceptions\Payment\PaymentVerificationException
     */
    public function verifyPaymentStatus(Payment $payment): Payment;

    /**
     * Check if this gateway can handle the given payment method.
     *
     * @param string $paymentMethod The payment method to check
     * @return bool
     */
    public function supports(string $paymentMethod): bool;

    /**
     * Get the name of this payment gateway.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the payment method this gateway handles.
     *
     * @return \App\Enums\Payment\PaymentMethodEnum
     */
    public function getPaymentMethod(): \App\Enums\Payment\PaymentMethodEnum;
}
