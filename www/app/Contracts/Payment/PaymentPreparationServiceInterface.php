<?php

namespace App\Contracts\Payment;

use App\Models\Payment\Payment;

interface PaymentPreparationServiceInterface
{
    /**
     * Register a payment method handler
     *
     * @param string $paymentMethod The payment method identifier
     * @param PaymentMethodHandlerInterface $handler The handler for this payment method
     */
    public function registerHandler(string $paymentMethod, PaymentMethodHandlerInterface $handler): void;

    /**
     * Prepare a payment by calling the appropriate handler
     *
     * @param Payment $payment The payment to prepare
     * @return string The redirect URL where the user should be sent
     * @throws \InvalidArgumentException If no handler is registered for the payment method
     */
    public function prepare(Payment $payment): string;
}
