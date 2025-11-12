<?php

namespace App\Contracts\Payment;

use App\DTOs\Payment\PaymentCallbackResultDTO;
use App\Enums\Payment\PaymentMethodEnum;
use App\Models\Payment\Payment;
use Illuminate\Http\Request;

/**
 * Interface for payment callback handlers.
 * Each payment method (Fake, PayPal, Stripe, etc.) implements this interface
 * to handle callbacks from their respective payment gateways.
 */
interface PaymentCallbackHandlerInterface
{
    /**
     * Handle the payment callback from the external service.
     *
     * @param Payment $payment The payment being processed
     * @param Request $request The HTTP request from the gateway
     * @return PaymentCallbackResultDTO Standardized result of the callback processing
     */
    public function handleCallback(Payment $payment, Request $request): PaymentCallbackResultDTO;

    /**
     * Get the payment method this handler supports.
     *
     * @return PaymentMethodEnum
     */
    public function getPaymentMethod(): PaymentMethodEnum;

    /**
     * Validate that the callback request is authentic and came from the payment gateway.
     * This should verify signatures, tokens, or other security measures.
     *
     * @param Payment $payment The payment being validated
     * @param Request $request The callback request
     * @return bool True if the callback is valid and authentic
     */
    public function validateCallback(Payment $payment, Request $request): bool;
}
