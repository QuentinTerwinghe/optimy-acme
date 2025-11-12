<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

use App\DTOs\Payment\PaymentCallbackResultDTO;
use App\Exceptions\Payment\PaymentCallbackException;
use App\Models\Payment\Payment;
use Illuminate\Http\Request;

/**
 * Interface for payment callback service.
 * Defines the contract for processing payment gateway callbacks.
 */
interface PaymentCallbackServiceInterface
{
    /**
     * Register a callback handler for a specific payment method.
     *
     * @param PaymentCallbackHandlerInterface $handler
     * @return void
     */
    public function registerHandler(PaymentCallbackHandlerInterface $handler): void;

    /**
     * Process a payment callback.
     *
     * @param Payment $payment The payment receiving the callback
     * @param Request $request The callback request from the gateway
     * @return PaymentCallbackResultDTO The result of the callback processing
     * @throws PaymentCallbackException
     */
    public function processCallback(Payment $payment, Request $request): PaymentCallbackResultDTO;

    /**
     * Get all registered handlers.
     *
     * @return array<string, PaymentCallbackHandlerInterface>
     */
    public function getHandlers(): array;
}
