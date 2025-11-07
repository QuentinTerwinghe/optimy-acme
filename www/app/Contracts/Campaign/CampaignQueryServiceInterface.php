<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Collection;

/**
 * Campaign Query Service Interface
 *
 * Defines the contract for campaign read operations
 * Follows Single Responsibility Principle - handles only queries/reads
 */
interface CampaignQueryServiceInterface
{
    /**
     * Get all active campaigns
     *
     * Returns campaigns that are:
     * - Status is ACTIVE
     * - End date is in the future
     * - Ordered by end date (soonest first)
     *
     * @return Collection<int, Campaign>
     */
    public function getActiveCampaigns(): Collection;

    /**
     * Get count of active campaigns
     *
     * Returns the count of campaigns that are:
     * - Status is ACTIVE
     * - End date is in the future
     *
     * @return int
     */
    public function getActiveCampaignsCount(): int;

    /**
     * Find a campaign by ID
     *
     * @param string $id
     * @return Campaign|null
     */
    public function findById(string $id): ?Campaign;

    /**
     * Get all campaigns
     *
     * @return Collection<int, Campaign>
     */
    public function getAllCampaigns(): Collection;

    /**
     * Get campaigns by status
     *
     * @param \App\Enums\CampaignStatus $status
     * @return Collection<int, Campaign>
     */
    public function getCampaignsByStatus(\App\Enums\CampaignStatus $status): Collection;
}
