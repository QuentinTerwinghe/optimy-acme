<?php

namespace App\Http\Controllers\Payment;

use App\Exceptions\Payment\PaymentCallbackException;
use App\Http\Controllers\Controller;
use App\Models\Payment\Payment;
use App\Services\Payment\PaymentCallbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller that handles payment callbacks from external payment gateways.
 * This is a single entry point for all payment methods (Fake, PayPal, Stripe, etc.).
 */
class PaymentCallbackController extends Controller
{
    public function __construct(
        private PaymentCallbackService $callbackService
    ) {
    }

    /**
     * Handle the payment callback from an external gateway.
     * This method validates the payment, delegates to the appropriate handler,
     * and redirects to the success or failure page.
     *
     * @param Payment $payment The payment being processed
     * @param Request $request The callback request from the gateway
     * @return RedirectResponse
     */
    public function handle(Payment $payment, Request $request): RedirectResponse
    {
        Log::info('Received payment callback', [
            'payment_id' => $payment->id,
            'payment_method' => $payment->payment_method->value,
            'request_method' => $request->method(),
        ]);

        try {
            // Process the callback through the service
            $result = $this->callbackService->processCallback($payment, $request);

            // Redirect to the appropriate page based on the result
            return redirect()
                ->route($result->redirectRoute, $result->redirectParams)
                ->with('payment_status', $result->status->value)
                ->with('success', $result->isSuccessful() ? 'Payment completed successfully!' : null)
                ->with('error', $result->isFailed() ? $result->errorMessage : null);
        } catch (PaymentCallbackException $e) {
            Log::error('Payment callback processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            // Redirect to a generic error page
            return redirect()
                ->route('dashboard')
                ->with('error', 'Unable to process payment callback. Please contact support.');
        } catch (\Exception $e) {
            Log::error('Unexpected error during payment callback', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Redirect to a generic error page
            return redirect()
                ->route('dashboard')
                ->with('error', 'An unexpected error occurred. Please contact support.');
        }
    }
}
