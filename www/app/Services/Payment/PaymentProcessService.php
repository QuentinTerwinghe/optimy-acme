<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentProcessServiceInterface;
use App\Enums\Donation\DonationStatus;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Exceptions\Payment\PaymentProcessingException;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for initializing payment processes.
 * Handles creation of donation and payment records in pending status.
 * Follows Single Responsibility Principle - only handles initialization.
 */
class PaymentProcessService implements PaymentProcessServiceInterface
{
    /**
     * Initialize payment process by creating donation and payment records.
     *
     * @param string $campaignId The campaign to donate to
     * @param string $userId The user making the donation
     * @param float $amount The donation amount
     * @param PaymentMethodEnum $paymentMethod The selected payment method
     * @param array<string, mixed> $metadata Additional metadata for the payment
     * @return array{donation: Donation, payment: Payment}
     * @throws PaymentProcessingException
     */
    public function initializePayment(
        string $campaignId,
        string $userId,
        float $amount,
        PaymentMethodEnum $paymentMethod,
        array $metadata = []
    ): array {
        try {
            // Start database transaction
            DB::beginTransaction();

            // Validate campaign exists
            $campaign = Campaign::findOrFail($campaignId);

            // Validate amount is positive
            if ($amount <= 0) {
                throw PaymentProcessingException::forPayment(
                    'new',
                    'Amount must be greater than zero'
                );
            }

            // Create donation in pending status
            $donation = $this->createDonation($campaign, $userId, $amount);

            // Create payment in pending status
            $payment = $this->createPayment($donation, $paymentMethod, $metadata);

            DB::commit();

            Log::info('Payment initialization successful', [
                'donation_id' => $donation->id,
                'payment_id' => $payment->id,
                'campaign_id' => $campaignId,
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => $paymentMethod->value,
            ]);

            return [
                'donation' => $donation,
                'payment' => $payment,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Payment initialization failed', [
                'campaign_id' => $campaignId,
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => $paymentMethod->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw payment processing exceptions
            if ($e instanceof PaymentProcessingException) {
                throw $e;
            }

            // Wrap other exceptions
            throw new PaymentProcessingException(
                'Failed to initialize payment: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create a donation record in pending status.
     *
     * @param Campaign $campaign
     * @param string $userId
     * @param float $amount
     * @return Donation
     */
    private function createDonation(Campaign $campaign, string $userId, float $amount): Donation
    {
        return Donation::create([
            'campaign_id' => $campaign->id,
            'user_id' => $userId,
            'amount' => $amount,
            'status' => DonationStatus::PENDING,
        ]);
    }

    /**
     * Create a payment record in pending status.
     *
     * @param Donation $donation
     * @param PaymentMethodEnum $paymentMethod
     * @param array<string, mixed> $metadata
     * @return Payment
     */
    private function createPayment(
        Donation $donation,
        PaymentMethodEnum $paymentMethod,
        array $metadata
    ): Payment {
        return Payment::create([
            'donation_id' => $donation->id,
            'payment_method' => $paymentMethod,
            'status' => PaymentStatusEnum::PENDING,
            'amount' => $donation->amount,
            'currency' => 'USD', // Could be configurable or from campaign
            'metadata' => $metadata,
            'initiated_at' => now(),
        ]);
    }
}
