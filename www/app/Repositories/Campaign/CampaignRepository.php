<?php

declare(strict_types=1);

namespace App\Repositories\Campaign;

use App\Contracts\Campaign\CampaignRepositoryInterface;
use App\Enums\Campaign\CampaignStatus;
use App\Models\Campaign\Campaign;
use Illuminate\Database\Eloquent\Collection;

/**
 * Campaign Repository
 *
 * Handles all data access operations for campaigns
 * Implements Repository Pattern - abstracts Eloquent ORM
 * Follows Single Responsibility Principle - only data access
 */
class CampaignRepository implements CampaignRepositoryInterface
{
    /**
     * Create a new campaign
     *
     * @param array<string, mixed> $data
     * @return Campaign
     */
    public function create(array $data): Campaign
    {
        return Campaign::create($data);
    }

    /**
     * Find a campaign by ID
     *
     * @param string $id
     * @return Campaign|null
     */
    public function find(string $id): ?Campaign
    {
        return Campaign::find($id);
    }

    /**
     * Find a campaign by ID or fail
     *
     * @param string $id
     * @return Campaign
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): Campaign
    {
        return Campaign::findOrFail($id);
    }

    /**
     * Find a campaign by ID with relationships loaded
     *
     * @param string $id
     * @param array<int, string> $relations
     * @return Campaign|null
     */
    public function findWithRelations(string $id, array $relations): ?Campaign
    {
        return Campaign::with($relations)->find($id);
    }

    /**
     * Update a campaign
     *
     * @param Campaign $campaign
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(Campaign $campaign, array $data): bool
    {
        return $campaign->update($data);
    }

    /**
     * Delete a campaign
     *
     * @param Campaign $campaign
     * @return bool
     */
    public function delete(Campaign $campaign): bool
    {
        return (bool) $campaign->delete();
    }

    /**
     * Get all campaigns
     *
     * @return Collection<int, Campaign>
     */
    public function getAll(): Collection
    {
        return Campaign::query()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get campaigns by status
     *
     * @param CampaignStatus $status
     * @return Collection<int, Campaign>
     */
    public function getByStatus(CampaignStatus $status): Collection
    {
        return Campaign::query()
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get campaigns by category
     *
     * @param int $categoryId
     * @param array<int, string> $relations
     * @return Collection<int, Campaign>
     */
    public function getByCategory(int $categoryId, array $relations = []): Collection
    {
        $query = Campaign::query()
            ->where('category_id', $categoryId)
            ->orderBy('created_at', 'desc');

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Get campaigns by tags (campaigns that have ANY of the specified tags)
     *
     * @param array<int, int> $tagIds
     * @param array<int, string> $relations
     * @return Collection<int, Campaign>
     */
    public function getByTags(array $tagIds, array $relations = []): Collection
    {
        $query = Campaign::query()
            ->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);
            })
            ->orderBy('created_at', 'desc');

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Get campaigns by all tags (campaigns that have ALL of the specified tags)
     *
     * @param array<int, int> $tagIds
     * @param array<int, string> $relations
     * @return Collection<int, Campaign>
     */
    public function getByAllTags(array $tagIds, array $relations = []): Collection
    {
        $tagCount = count($tagIds);

        $query = Campaign::query()
            ->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);
            }, '=', $tagCount)
            ->orderBy('created_at', 'desc');

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Get active campaigns with optional filters
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Campaign>
     */
    public function getActiveCampaigns(array $filters = []): Collection
    {
        $query = Campaign::query()
            ->where('status', CampaignStatus::ACTIVE)
            ->where('start_date', '<=', now())
            ->where('end_date', '>', now());

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('title', 'LIKE', "%{$search}%");
        }

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply tags filter
        if (!empty($filters['tag_ids']) && is_array($filters['tag_ids'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tags.id', $filters['tag_ids']);
            });
        }

        return $query
            ->with(['category', 'tags'])
            ->orderBy('end_date', 'asc')
            ->get();
    }

    /**
     * Get campaigns for a specific user (created by)
     *
     * @param int|string $userId
     * @param array<int, string> $relations
     * @return Collection<int, Campaign>
     */
    public function getByCreator(int|string $userId, array $relations = []): Collection
    {
        $query = Campaign::query()
            ->where('created_by', $userId)
            ->orderBy('created_at', 'desc');

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Count campaigns by status
     *
     * @param CampaignStatus $status
     * @return int
     */
    public function countByStatus(CampaignStatus $status): int
    {
        return Campaign::query()
            ->where('status', $status)
            ->count();
    }

    /**
     * Count active campaigns
     *
     * @return int
     */
    public function countActiveCampaigns(): int
    {
        return Campaign::query()
            ->where('status', CampaignStatus::ACTIVE)
            ->where('start_date', '<=', now())
            ->where('end_date', '>', now())
            ->count();
    }

    /**
     * Sum field by status
     *
     * @param string $field
     * @param array<int, CampaignStatus> $statuses
     * @return float
     */
    public function sumByStatus(string $field, array $statuses): float
    {
        $total = Campaign::query()
            ->whereIn('status', $statuses)
            ->sum($field);

        return (float) $total;
    }

    /**
     * Get campaigns with aggregated data
     *
     * @param array<int, CampaignStatus> $statuses
     * @return array{total_goal: float, total_raised: float}
     */
    public function getAggregatedFundingData(array $statuses): array
    {
        $result = Campaign::query()
            ->whereIn('status', $statuses)
            ->selectRaw('SUM(goal_amount) as total_goal, SUM(current_amount) as total_raised')
            ->first();

        return [
            'total_goal' => (float) ($result->total_goal ?? 0),
            'total_raised' => (float) ($result->total_raised ?? 0),
        ];
    }

    /**
     * Load relationships on a campaign
     *
     * @param Campaign $campaign
     * @param array<int, string> $relations
     * @return Campaign
     */
    public function loadRelations(Campaign $campaign, array $relations): Campaign
    {
        $campaign->load($relations);

        return $campaign;
    }
}
