<?php

namespace App\Exceptions\Payment;

/**
 * Exception thrown when an unsupported payment method is requested.
 */
class UnsupportedPaymentMethodException extends PaymentException
{
    /**
     * Create a new exception for an unsupported payment method.
     */
    public static function forMethod(string $paymentMethod): self
    {
        return new self("Unsupported payment method: {$paymentMethod}");
    }

    /**
     * Create exception when no gateway is available.
     */
    public static function noGatewayAvailable(string $paymentMethod): self
    {
        return new self("No payment gateway available for method: {$paymentMethod}");
    }
}
