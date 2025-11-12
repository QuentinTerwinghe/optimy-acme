<?php

namespace App\Services\Payment\CallbackHandlers;

use App\Contracts\Payment\PaymentCallbackHandlerInterface;
use App\DTOs\Payment\PaymentCallbackResultDTO;
use App\Enums\Payment\PaymentMethodEnum;
use App\Models\Payment\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles callbacks from the fake payment gateway.
 * This is used for testing and development purposes.
 */
class FakePaymentCallbackHandler implements PaymentCallbackHandlerInterface
{
    /**
     * Handle the payment callback from the fake payment service.
     *
     * @param Payment $payment The payment being processed
     * @param Request $request The HTTP request from the gateway
     * @return PaymentCallbackResultDTO Standardized result of the callback processing
     */
    public function handleCallback(Payment $payment, Request $request): PaymentCallbackResultDTO
    {
        Log::info('Processing fake payment callback', [
            'payment_id' => $payment->id,
            'request_data' => $request->all(),
        ]);

        // Extract callback data from the request
        $status = $request->input('status'); // 'success' or 'failed'
        $transactionId = $request->input('transaction_id');
        $errorMessage = $request->input('error_message');
        $errorCode = $request->input('error_code');

        // Build gateway response data
        $gatewayResponse = [
            'gateway' => 'fake',
            'callback_received_at' => now()->toISOString(),
            'session_id' => $request->input('session_id'),
            'raw_status' => $status,
        ];

        // Determine if payment was successful
        if ($status === 'success' && $transactionId) {
            Log::info('Fake payment successful', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            return PaymentCallbackResultDTO::success(
                transactionId: $transactionId,
                gatewayResponse: $gatewayResponse,
                redirectRoute: 'payment.result',
                redirectParams: ['payment' => $payment->id]
            );
        }

        // Payment failed
        Log::warning('Fake payment failed', [
            'payment_id' => $payment->id,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
        ]);

        return PaymentCallbackResultDTO::failed(
            errorMessage: $errorMessage ?? 'Payment was not successful',
            errorCode: $errorCode,
            gatewayResponse: $gatewayResponse,
            redirectRoute: 'payment.result',
            redirectParams: ['payment' => $payment->id]
        );
    }

    /**
     * Get the payment method this handler supports.
     *
     * @return PaymentMethodEnum
     */
    public function getPaymentMethod(): PaymentMethodEnum
    {
        return PaymentMethodEnum::FAKE;
    }

    /**
     * Validate that the callback request is authentic.
     * For fake payments, we just verify basic data is present.
     *
     * @param Payment $payment The payment being validated
     * @param Request $request The callback request
     * @return bool True if the callback is valid
     */
    public function validateCallback(Payment $payment, Request $request): bool
    {
        // For fake payment, we do basic validation
        // In real implementations, this would verify signatures, tokens, etc.

        // Check that required fields are present
        if (!$request->has('status')) {
            Log::warning('Fake payment callback missing status', [
                'payment_id' => $payment->id,
            ]);
            return false;
        }

        // Verify the session ID matches if present in payload
        $sessionId = $request->input('session_id');
        $payloadSessionId = $payment->payload['session_id'] ?? null;

        if ($sessionId && $payloadSessionId && $sessionId !== $payloadSessionId) {
            Log::warning('Fake payment callback session mismatch', [
                'payment_id' => $payment->id,
                'expected_session' => $payloadSessionId,
                'received_session' => $sessionId,
            ]);
            return false;
        }

        return true;
    }
}
