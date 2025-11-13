<?php

declare(strict_types=1);

namespace App\Contracts\Donation;

use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;

/**
 * Interface for donation service operations.
 * Follows Single Responsibility Principle (SRP) - handles ONLY donation business logic.
 * Follows Dependency Inversion Principle (DIP) - services depend on this abstraction.
 */
interface DonationServiceInterface
{
    /**
     * Get donation page data for a campaign.
     *
     * @param Campaign $campaign The campaign to donate to
     * @return array<string, mixed> Campaign data for donation page
     */
    public function getDonationPageData(Campaign $campaign): array;

    /**
     * Get quick donation amounts based on campaign currency.
     *
     * @param Campaign $campaign The campaign
     * @return array<int, int> Quick donation amounts
     */
    public function getQuickDonationAmounts(Campaign $campaign): array;

    /**
     * Mark a donation as successful after payment completion.
     *
     * @param Donation $donation The donation to mark as successful
     * @param Payment $payment The associated payment
     * @return Donation Updated donation
     */
    public function markDonationAsSuccessful(Donation $donation, Payment $payment): Donation;

    /**
     * Mark a donation as failed after payment failure or refund.
     *
     * @param Donation $donation The donation to mark as failed
     * @param Payment $payment The associated payment
     * @param string|null $errorMessage Optional error message
     * @return Donation Updated donation
     */
    public function markDonationAsFailed(Donation $donation, Payment $payment, ?string $errorMessage = null): Donation;

    /**
     * Check if a donation can be processed.
     *
     * @param Donation $donation The donation to check
     * @return bool True if donation can be processed
     */
    public function canProcessDonation(Donation $donation): bool;
}
