<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

use App\Models\Campaign\Campaign;
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
     * @param array<string, mixed> $filters Optional filters (search, category_id, tag_ids)
     * @return Collection<int, Campaign>
     */
    public function getActiveCampaigns(array $filters = []): Collection;

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
     * @param \App\Enums\Campaign\CampaignStatus $status
     * @return Collection<int, Campaign>
     */
    public function getCampaignsByStatus(\App\Enums\Campaign\CampaignStatus $status): Collection;

    /**
     * Get campaigns by category
     *
     * @param int $categoryId
     * @return Collection<int, Campaign>
     */
    public function getCampaignsByCategory(int $categoryId): Collection;

    /**
     * Get campaigns by tags (campaigns that have ANY of the specified tags)
     *
     * @param array<int, int> $tagIds
     * @return Collection<int, Campaign>
     */
    public function getCampaignsByTags(array $tagIds): Collection;

    /**
     * Get campaigns by tags (campaigns that have ALL of the specified tags)
     *
     * @param array<int, int> $tagIds
     * @return Collection<int, Campaign>
     */
    public function getCampaignsByAllTags(array $tagIds): Collection;

    /**
     * Get campaigns for management view
     *
     * Returns campaigns based on user role:
     * - Campaign managers and admins: all campaigns
     * - Regular users: only their own campaigns
     *
     * @param \App\Models\Auth\User $user
     * @return Collection<int, Campaign>
     */
    public function getCampaignsForManagement(\App\Models\Auth\User $user): Collection;
}
