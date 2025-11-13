<?php

declare(strict_types=1);

namespace App\Repositories\Donation;

use App\Contracts\Donation\DonationRepositoryInterface;
use App\Enums\Donation\DonationStatus;
use App\Models\Donation\Donation;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for donation data access.
 * Follows Repository Pattern - abstracts database operations.
 * Follows Single Responsibility Principle (SRP) - handles ONLY data access.
 */
class DonationRepository implements DonationRepositoryInterface
{
    /**
     * Find a donation by ID.
     *
     * @param string $id
     * @return Donation|null
     */
    public function findById(string $id): ?Donation
    {
        return Donation::find($id);
    }

    /**
     * Update a donation.
     *
     * @param Donation $donation
     * @param array<string, mixed> $data
     * @return Donation
     */
    public function update(Donation $donation, array $data): Donation
    {
        $donation->update($data);
        $freshDonation = $donation->fresh();

        if ($freshDonation === null) {
            throw new \RuntimeException('Failed to refresh donation after update');
        }

        return $freshDonation;
    }

    /**
     * Mark donation as successful.
     *
     * @param Donation $donation
     * @return Donation
     */
    public function markAsSuccessful(Donation $donation): Donation
    {
        return $this->update($donation, [
            'status' => DonationStatus::SUCCESS,
            'error_message' => null,
        ]);
    }

    /**
     * Mark donation as failed with optional error message.
     *
     * @param Donation $donation
     * @param string|null $errorMessage
     * @return Donation
     */
    public function markAsFailed(Donation $donation, ?string $errorMessage = null): Donation
    {
        return $this->update($donation, [
            'status' => DonationStatus::FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get all donations for a campaign.
     *
     * @param string $campaignId
     * @return Collection<int, Donation>
     */
    public function getDonationsByCampaign(string $campaignId): Collection
    {
        return Donation::where('campaign_id', $campaignId)->get();
    }

    /**
     * Get all donations for a user.
     *
     * @param string $userId
     * @return Collection<int, Donation>
     */
    public function getDonationsByUser(string $userId): Collection
    {
        return Donation::where('user_id', $userId)->get();
    }
}
