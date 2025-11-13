<?php

declare(strict_types=1);

namespace App\Services\Donation;

use App\Contracts\Donation\DonationRepositoryInterface;
use App\Contracts\Donation\DonationServiceInterface;
use App\Enums\Donation\DonationStatus;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing donation business logic.
 * Follows Single Responsibility Principle (SRP) - handles ONLY donation business logic.
 * Follows Dependency Inversion Principle (DIP) - depends on DonationRepositoryInterface.
 * This service was refactored to extract donation logic from PaymentService.
 */
class DonationService implements DonationServiceInterface
{
    /**
     * Create a new donation service instance.
     *
     * @param DonationRepositoryInterface $repository Donation repository
     */
    public function __construct(
        private readonly DonationRepositoryInterface $repository
    ) {}

    /**
     * Get donation page data for a campaign.
     *
     * @param Campaign $campaign The campaign to donate to
     * @return array<string, mixed> Campaign data for donation page
     */
    public function getDonationPageData(Campaign $campaign): array
    {
        return [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'description' => $campaign->description,
                'currency' => $campaign->currency?->value,
                'goal_amount' => $campaign->goal_amount,
                'current_amount' => $campaign->current_amount,
            ],
        ];
    }

    /**
     * Get quick donation amounts based on campaign currency.
     *
     * @param Campaign $campaign The campaign
     * @return array<int, int> Quick donation amounts
     */
    public function getQuickDonationAmounts(Campaign $campaign): array
    {
        // Standard quick donation amounts
        return [5, 10, 20, 50, 100];
    }

    /**
     * Mark a donation as successful after payment completion.
     * Extracted from PaymentService to follow SRP.
     *
     * @param Donation $donation The donation to mark as successful
     * @param Payment $payment The associated payment
     * @return Donation Updated donation
     */
    public function markDonationAsSuccessful(Donation $donation, Payment $payment): Donation
    {
        // Only update if not already successful
        if ($donation->status !== DonationStatus::SUCCESS) {
            $updatedDonation = $this->repository->markAsSuccessful($donation);

            Log::info('Donation marked as successful after payment', [
                'donation_id' => $donation->id,
                'payment_id' => $payment->id,
                'campaign_id' => $donation->campaign_id,
                'amount' => $donation->amount,
            ]);

            return $updatedDonation;
        }

        return $donation;
    }

    /**
     * Mark a donation as failed after payment failure or refund.
     * Extracted from PaymentService to follow SRP.
     *
     * @param Donation $donation The donation to mark as failed
     * @param Payment $payment The associated payment
     * @param string|null $errorMessage Optional error message
     * @return Donation Updated donation
     */
    public function markDonationAsFailed(Donation $donation, Payment $payment, ?string $errorMessage = null): Donation
    {
        $updatedDonation = $this->repository->markAsFailed($donation, $errorMessage);

        Log::info('Donation marked as failed', [
            'donation_id' => $donation->id,
            'payment_id' => $payment->id,
            'campaign_id' => $donation->campaign_id,
            'error_message' => $errorMessage,
        ]);

        return $updatedDonation;
    }

    /**
     * Check if a donation can be processed.
     *
     * @param Donation $donation The donation to check
     * @return bool True if donation can be processed
     */
    public function canProcessDonation(Donation $donation): bool
    {
        // A donation can be processed if it's pending or failed (retry)
        return $donation->status === DonationStatus::PENDING
            || $donation->status === DonationStatus::FAILED;
    }
}
