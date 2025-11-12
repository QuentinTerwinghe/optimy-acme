<?php

namespace App\Contracts\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;

/**
 * Interface for payment service operations.
 */
interface PaymentServiceInterface
{
    /**
     * Create a payment record for a donation.
     *
     * @param Donation $donation
     * @param PaymentMethodEnum $paymentMethod
     * @param array<string, mixed> $metadata
     * @return Payment
     */
    public function createPayment(
        Donation $donation,
        PaymentMethodEnum $paymentMethod,
        array $metadata = []
    ): Payment;

    /**
     * Process a payment through the appropriate gateway.
     *
     * @param Payment $payment
     * @param array<string, mixed> $paymentData
     * @return Payment
     * @throws \App\Exceptions\Payment\PaymentProcessingException
     * @throws \App\Exceptions\Payment\UnsupportedPaymentMethodException
     */
    public function processPayment(Payment $payment, array $paymentData = []): Payment;

    /**
     * Refund a payment.
     *
     * @param Payment $payment
     * @param float|null $amount
     * @return Payment
     * @throws \App\Exceptions\Payment\PaymentRefundException
     * @throws \App\Exceptions\Payment\UnsupportedPaymentMethodException
     */
    public function refundPayment(Payment $payment, ?float $amount = null): Payment;

    /**
     * Verify the status of a payment with the gateway.
     *
     * @param Payment $payment
     * @return Payment
     * @throws \App\Exceptions\Payment\UnsupportedPaymentMethodException
     */
    public function verifyPaymentStatus(Payment $payment): Payment;

    /**
     * Get all available payment methods.
     *
     * @return array<PaymentMethodEnum>
     */
    public function getAvailablePaymentMethods(): array;

    /**
     * Get payment statistics for a donation.
     *
     * @param Donation $donation
     * @return array<string, mixed>
     */
    public function getPaymentStatistics(Donation $donation): array;
}
