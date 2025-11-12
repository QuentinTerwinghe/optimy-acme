<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentMethodHandlerInterface;
use App\Contracts\Payment\PaymentPreparationServiceInterface;
use App\Models\Payment\Payment;
use Illuminate\Support\Facades\Log;

class PaymentPreparationService implements PaymentPreparationServiceInterface
{
    /**
     * @param array<string, PaymentMethodHandlerInterface> $handlers
     */
    public function __construct(
        private array $handlers = []
    ) {}

    /**
     * Register a payment method handler
     */
    public function registerHandler(string $paymentMethod, PaymentMethodHandlerInterface $handler): void
    {
        $this->handlers[$paymentMethod] = $handler;
    }

    /**
     * Prepare a payment by calling the appropriate handler
     *
     * @throws \InvalidArgumentException If no handler is registered for the payment method
     */
    public function prepare(Payment $payment): string
    {
        $paymentMethod = $payment->payment_method->value;

        if (!isset($this->handlers[$paymentMethod])) {
            throw new \InvalidArgumentException("No handler registered for payment method: {$paymentMethod}");
        }

        $handler = $this->handlers[$paymentMethod];

        Log::info('Preparing payment', [
            'payment_id' => $payment->id,
            'payment_method' => $paymentMethod,
        ]);

        // Call the handler to prepare the payment
        $result = $handler->prepare($payment);

        // Save the preparation data
        $payment->markAsPrepared($result->payload, $result->redirectUrl);

        Log::info('Payment prepared successfully', [
            'payment_id' => $payment->id,
            'redirect_url' => $result->redirectUrl,
        ]);

        return $result->redirectUrl;
    }
}
