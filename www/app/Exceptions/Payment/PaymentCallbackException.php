<?php

namespace App\Exceptions\Payment;

use Exception;

/**
 * Exception thrown when payment callback processing fails.
 */
class PaymentCallbackException extends Exception
{
    /**
     * Create an exception for an invalid callback.
     *
     * @param string $paymentId
     * @return self
     */
    public static function invalidCallback(string $paymentId): self
    {
        return new self(
            "Payment callback validation failed for payment ID: {$paymentId}"
        );
    }

    /**
     * Create an exception when no handler is registered for a payment method.
     *
     * @param string $paymentMethod
     * @return self
     */
    public static function noHandlerForMethod(string $paymentMethod): self
    {
        return new self(
            "No callback handler registered for payment method: {$paymentMethod}"
        );
    }

    /**
     * Create an exception when callback processing fails.
     *
     * @param string $paymentId
     * @param string $reason
     * @return self
     */
    public static function processingFailed(string $paymentId, string $reason): self
    {
        return new self(
            "Failed to process payment callback for payment ID {$paymentId}: {$reason}"
        );
    }
}
