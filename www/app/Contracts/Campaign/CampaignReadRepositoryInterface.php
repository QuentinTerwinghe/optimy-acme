<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

use App\Enums\Campaign\CampaignStatus;
use App\Models\Campaign\Campaign;
use Illuminate\Database\Eloquent\Collection;

/**
 * Campaign Read Repository Interface
 *
 * Defines the contract for reading campaign data
 * Follows Interface Segregation Principle - focused on read operations only
 */
interface CampaignReadRepositoryInterface
{
    /**
     * Find a campaign by ID
     *
     * @param string $id
     * @return Campaign|null
     */
    public function find(string $id): ?Campaign;

    /**
     * Find a campaign by ID or fail
     *
     * @param string $id
     * @return Campaign
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): Campaign;

    /**
     * Find a campaign by ID with relationships loaded
     *
     * @param string $id
     * @param array<int, string> $relations
     * @return Campaign|null
     */
    public function findWithRelations(string $id, array $relations): ?Campaign;

    /**
     * Get all campaigns
     *
     * @return Collection<int, Campaign>
     */
    public function getAll(): Collection;

    /**
     * Get campaigns by status
     *
     * @param CampaignStatus $status
     * @return Collection<int, Campaign>
     */
    public function getByStatus(CampaignStatus $status): Collection;

    /**
     * Get campaigns by category
     *
     * @param int $categoryId
     * @param array<int, string> $relations
     * @return Collection<int, Campaign>
     */
    public function getByCategory(int $categoryId, array $relations = []): Collection;

    /**
     * Get campaigns by tags (campaigns that have ANY of the specified tags)
     *
     * @param array<int, int> $tagIds
     * @param array<int, string> $relations
     * @return Collection<int, Campaign>
     */
    public function getByTags(array $tagIds, array $relations = []): Collection;

    /**
     * Get campaigns by all tags (campaigns that have ALL of the specified tags)
     *
     * @param array<int, int> $tagIds
     * @param array<int, string> $relations
     * @return Collection<int, Campaign>
     */
    public function getByAllTags(array $tagIds, array $relations = []): Collection;

    /**
     * Get active campaigns with optional filters
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Campaign>
     */
    public function getActiveCampaigns(array $filters = []): Collection;

    /**
     * Get campaigns for a specific user (created by)
     *
     * @param int|string $userId
     * @param array<int, string> $relations
     * @return Collection<int, Campaign>
     */
    public function getByCreator(int|string $userId, array $relations = []): Collection;

    /**
     * Load relationships on a campaign
     *
     * @param Campaign $campaign
     * @param array<int, string> $relations
     * @return Campaign
     */
    public function loadRelations(Campaign $campaign, array $relations): Campaign;
}
