<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentCallbackHandlerInterface;
use App\Contracts\Payment\PaymentCallbackServiceInterface;
use App\DTOs\Payment\PaymentCallbackResultDTO;
use App\Enums\Donation\DonationStatus;
use App\Enums\Notification\NotificationType;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Exceptions\Payment\PaymentCallbackException;
use App\Jobs\Campaign\UpdateCampaignAmountJob;
use App\Jobs\Notification\SendNewDonationNotificationJob;
use App\Jobs\Notification\SendPaymentNotificationJob;
use App\Models\Payment\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service that orchestrates payment callback processing.
 * This service resolves the appropriate handler for each payment method
 * and coordinates the update of Payment and Donation models.
 */
class PaymentCallbackService implements PaymentCallbackServiceInterface
{
    /**
     * @var array<string, PaymentCallbackHandlerInterface>
     */
    private array $handlers = [];

    /**
     * Register a callback handler for a specific payment method.
     *
     * @param PaymentCallbackHandlerInterface $handler
     * @return void
     */
    public function registerHandler(PaymentCallbackHandlerInterface $handler): void
    {
        $paymentMethod = $handler->getPaymentMethod()->value;
        $this->handlers[$paymentMethod] = $handler;

        Log::debug('Registered payment callback handler', [
            'payment_method' => $paymentMethod,
            'handler_class' => get_class($handler),
        ]);
    }

    /**
     * Process a payment callback.
     *
     * @param Payment $payment The payment receiving the callback
     * @param Request $request The callback request from the gateway
     * @return PaymentCallbackResultDTO The result of the callback processing
     * @throws PaymentCallbackException
     */
    public function processCallback(Payment $payment, Request $request): PaymentCallbackResultDTO
    {
        Log::info('Processing payment callback', [
            'payment_id' => $payment->id,
            'payment_method' => $payment->payment_method->value,
            'current_status' => $payment->status->value,
        ]);

        // Get the appropriate handler for this payment method
        $handler = $this->resolveHandler($payment->payment_method);

        // Validate the callback is authentic
        if (!$handler->validateCallback($payment, $request)) {
            Log::error('Payment callback validation failed', [
                'payment_id' => $payment->id,
                'payment_method' => $payment->payment_method->value,
            ]);

            throw PaymentCallbackException::invalidCallback((string) $payment->id);
        }

        // Process the callback and get the result
        $result = $handler->handleCallback($payment, $request);

        // Update the payment and donation in a transaction
        DB::beginTransaction();
        try {
            $this->updatePayment($payment, $result);
            $this->updateDonation($payment, $result);

            DB::commit();

            Log::info('Payment callback processed successfully', [
                'payment_id' => $payment->id,
                'final_status' => $result->status->value,
                'transaction_id' => $result->transactionId,
            ]);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update payment/donation after callback', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw PaymentCallbackException::processingFailed((string) $payment->id, $e->getMessage());
        }
    }

    /**
     * Resolve the handler for a given payment method.
     *
     * @param PaymentMethodEnum $paymentMethod
     * @return PaymentCallbackHandlerInterface
     * @throws PaymentCallbackException
     */
    private function resolveHandler(PaymentMethodEnum $paymentMethod): PaymentCallbackHandlerInterface
    {
        $handler = $this->handlers[$paymentMethod->value] ?? null;

        if (!$handler) {
            Log::error('No callback handler registered for payment method', [
                'payment_method' => $paymentMethod->value,
                'registered_handlers' => array_keys($this->handlers),
            ]);

            throw PaymentCallbackException::noHandlerForMethod($paymentMethod->value);
        }

        return $handler;
    }

    /**
     * Update the payment model based on the callback result.
     * Also dispatches notification jobs to inform the payment initiator.
     *
     * @param Payment $payment
     * @param PaymentCallbackResultDTO $result
     * @return void
     */
    private function updatePayment(Payment $payment, PaymentCallbackResultDTO $result): void
    {
        // Load donation with user relationship to get the payment initiator
        $payment->load('donation.user');
        $donation = $payment->donation;

        if ($result->isSuccessful()) {
            $payment->markAsCompleted(
                $result->transactionId ?? '',
                $result->gatewayResponse
            );

            // Dispatch job to send payment success notification to the payment initiator
            if ($donation) {
                SendPaymentNotificationJob::dispatch(
                    $donation->user_id,
                    (string) $payment->id,
                    NotificationType::PAYMENT_SUCCESS
                );

                Log::info('Payment success notification job dispatched', [
                    'payment_id' => $payment->id,
                    'user_id' => $donation->user_id,
                ]);
            }
        } elseif ($result->isFailed()) {
            $payment->markAsFailed(
                $result->errorMessage ?? 'Payment failed',
                $result->errorCode,
                $result->gatewayResponse
            );

            // Dispatch job to send payment failure notification to the payment initiator
            if ($donation) {
                SendPaymentNotificationJob::dispatch(
                    $donation->user_id,
                    (string) $payment->id,
                    NotificationType::PAYMENT_FAILURE
                );

                Log::info('Payment failure notification job dispatched', [
                    'payment_id' => $payment->id,
                    'user_id' => $donation->user_id,
                ]);
            }
        }
    }

    /**
     * Update the donation status based on the payment result.
     *
     * @param Payment $payment
     * @param PaymentCallbackResultDTO $result
     * @return void
     */
    private function updateDonation(Payment $payment, PaymentCallbackResultDTO $result): void
    {
        $donation = $payment->donation;

        if (!$donation) {
            Log::warning('Payment has no associated donation', [
                'payment_id' => $payment->id,
            ]);
            return;
        }

        if ($result->isSuccessful()) {
            $donation->update([
                'status' => DonationStatus::SUCCESS,
                'error_message' => null,
            ]);

            Log::info('Donation marked as successful', [
                'donation_id' => $donation->id,
                'payment_id' => $payment->id,
                'campaign_id' => $donation->campaign_id,
            ]);

            // Dispatch job to update campaign amount via RabbitMQ
            // This ensures campaign amounts are updated sequentially and consistently
            UpdateCampaignAmountJob::dispatch($donation->campaign_id);

            Log::info('Campaign amount update job dispatched', [
                'donation_id' => $donation->id,
                'campaign_id' => $donation->campaign_id,
            ]);

            // Dispatch job to send new donation notification to campaign creator
            SendNewDonationNotificationJob::dispatch($donation->id);

            Log::info('New donation notification job dispatched', [
                'donation_id' => $donation->id,
                'campaign_id' => $donation->campaign_id,
            ]);
        } elseif ($result->isFailed()) {
            $donation->update([
                'status' => DonationStatus::FAILED,
                'error_message' => $result->errorMessage,
            ]);

            Log::info('Donation marked as failed', [
                'donation_id' => $donation->id,
                'payment_id' => $payment->id,
                'error' => $result->errorMessage,
            ]);
        }
    }

    /**
     * Get all registered handlers.
     *
     * @return array<string, PaymentCallbackHandlerInterface>
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}
