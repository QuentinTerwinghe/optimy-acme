<?php

namespace App\Exceptions\Payment;

/**
 * Exception thrown when a payment processing fails.
 */
class PaymentProcessingException extends PaymentException
{
    /**
     * Create a new exception instance.
     */
    public static function forPayment(string $paymentId, string $reason): self
    {
        return new self("Payment processing failed for payment {$paymentId}: {$reason}");
    }

    /**
     * Create exception for gateway error.
     */
    public static function gatewayError(string $gatewayName, string $error): self
    {
        return new self("Payment gateway {$gatewayName} returned an error: {$error}");
    }
}
