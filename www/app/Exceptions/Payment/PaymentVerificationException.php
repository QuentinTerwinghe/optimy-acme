<?php

namespace App\Exceptions\Payment;

/**
 * Exception thrown when payment verification fails.
 */
class PaymentVerificationException extends PaymentException
{
    /**
     * Create a new exception for a verification failure.
     */
    public static function forPayment(string $paymentId, string $reason): self
    {
        return new self("Payment verification failed for payment {$paymentId}: {$reason}");
    }
}
