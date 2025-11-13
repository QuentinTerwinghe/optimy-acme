<?php

declare(strict_types=1);

namespace App\Contracts\Donation;

use App\Models\Donation\Donation;

/**
 * Interface for donation repository.
 * Follows Repository Pattern - abstracts data access layer.
 * Follows Dependency Inversion Principle (DIP) - services depend on abstractions.
 */
interface DonationRepositoryInterface
{
    /**
     * Find a donation by ID.
     *
     * @param string $id
     * @return Donation|null
     */
    public function findById(string $id): ?Donation;

    /**
     * Update a donation.
     *
     * @param Donation $donation
     * @param array<string, mixed> $data
     * @return Donation
     */
    public function update(Donation $donation, array $data): Donation;

    /**
     * Mark donation as successful.
     *
     * @param Donation $donation
     * @return Donation
     */
    public function markAsSuccessful(Donation $donation): Donation;

    /**
     * Mark donation as failed with optional error message.
     *
     * @param Donation $donation
     * @param string|null $errorMessage
     * @return Donation
     */
    public function markAsFailed(Donation $donation, ?string $errorMessage = null): Donation;

    /**
     * Get all donations for a campaign.
     *
     * @param string $campaignId
     * @return \Illuminate\Database\Eloquent\Collection<int, Donation>
     */
    public function getDonationsByCampaign(string $campaignId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get all donations for a user.
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection<int, Donation>
     */
    public function getDonationsByUser(string $userId): \Illuminate\Database\Eloquent\Collection;
}
