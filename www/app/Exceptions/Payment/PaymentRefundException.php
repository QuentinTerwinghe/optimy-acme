<?php

namespace App\Exceptions\Payment;

/**
 * Exception thrown when a payment refund fails.
 */
class PaymentRefundException extends PaymentException
{
    /**
     * Create a new exception for a refund failure.
     */
    public static function forPayment(string $paymentId, string $reason): self
    {
        return new self("Payment refund failed for payment {$paymentId}: {$reason}");
    }

    /**
     * Create exception for invalid refund amount.
     */
    public static function invalidAmount(float $requestedAmount, float $paymentAmount): self
    {
        return new self("Cannot refund {$requestedAmount}: exceeds payment amount of {$paymentAmount}");
    }

    /**
     * Create exception for non-refundable payment.
     */
    public static function notRefundable(string $paymentId, string $status): self
    {
        return new self("Payment {$paymentId} cannot be refunded. Current status: {$status}");
    }
}
