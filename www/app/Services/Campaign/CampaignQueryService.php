<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Contracts\Campaign\CampaignQueryServiceInterface;
use App\Contracts\Campaign\CampaignReadRepositoryInterface;
use App\Contracts\Campaign\CampaignAggregateRepositoryInterface;
use App\Enums\Campaign\CampaignPermissions;
use App\Enums\Campaign\CampaignStatus;
use App\Models\Campaign\Campaign;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Campaign Query Service
 *
 * Handles all read operations for campaigns
 * Follows Single Responsibility Principle - only queries/reads
 * Follows Dependency Inversion Principle - depends on abstractions (interfaces)
 */
class CampaignQueryService implements CampaignQueryServiceInterface
{
    /**
     * Constructor
     *
     * @param CampaignReadRepositoryInterface $readRepository
     * @param CampaignAggregateRepositoryInterface $aggregateRepository
     */
    public function __construct(
        private CampaignReadRepositoryInterface $readRepository,
        private CampaignAggregateRepositoryInterface $aggregateRepository
    ) {}
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
    public function getActiveCampaigns(array $filters = []): Collection
    {
        try {
            return $this->readRepository->getActiveCampaigns($filters);
        } catch (\Exception $e) {
            Log::error('Failed to fetch active campaigns', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return empty collection on error
            return new Collection();
        }
    }

    /**
     * Get count of active campaigns
     *
     * Returns the count of campaigns that are:
     * - Status is ACTIVE
     * - End date is in the future
     *
     * @return int
     */
    public function getActiveCampaignsCount(): int
    {
        try {
            return $this->aggregateRepository->countActiveCampaigns();
        } catch (\Exception $e) {
            Log::error('Failed to count active campaigns', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 0 on error
            return 0;
        }
    }

    /**
     * Find a campaign by ID
     *
     * @param string $id
     * @return Campaign|null
     */
    public function findById(string $id): ?Campaign
    {
        try {
            return $this->readRepository->find($id);
        } catch (\Exception $e) {
            Log::error('Failed to find campaign by ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find a campaign by ID with relationships loaded
     *
     * @param string $id
     * @param array<int, string> $relations
     * @return Campaign|null
     */
    public function findByIdWithRelations(string $id, array $relations = ['category', 'tags', 'creator']): ?Campaign
    {
        try {
            return $this->readRepository->findWithRelations($id, $relations);
        } catch (\Exception $e) {
            Log::error('Failed to find campaign by ID with relations', [
                'id' => $id,
                'relations' => $relations,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get all campaigns
     *
     * @return Collection<int, Campaign>
     */
    public function getAllCampaigns(): Collection
    {
        try {
            return $this->readRepository->getAll();
        } catch (\Exception $e) {
            Log::error('Failed to fetch all campaigns', [
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Get campaigns by status
     *
     * @param CampaignStatus $status
     * @return Collection<int, Campaign>
     */
    public function getCampaignsByStatus(CampaignStatus $status): Collection
    {
        try {
            return $this->readRepository->getByStatus($status);
        } catch (\Exception $e) {
            Log::error('Failed to fetch campaigns by status', [
                'status' => $status->value,
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Get campaigns by category
     *
     * @param int $categoryId
     * @return Collection<int, Campaign>
     */
    public function getCampaignsByCategory(int $categoryId): Collection
    {
        try {
            return $this->readRepository->getByCategory($categoryId, ['category', 'tags']);
        } catch (\Exception $e) {
            Log::error('Failed to fetch campaigns by category', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Get campaigns by tags (campaigns that have ANY of the specified tags)
     *
     * @param array<int, int> $tagIds
     * @return Collection<int, Campaign>
     */
    public function getCampaignsByTags(array $tagIds): Collection
    {
        try {
            return $this->readRepository->getByTags($tagIds, ['category', 'tags']);
        } catch (\Exception $e) {
            Log::error('Failed to fetch campaigns by tags', [
                'tag_ids' => $tagIds,
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Get campaigns by tags (campaigns that have ALL of the specified tags)
     *
     * @param array<int, int> $tagIds
     * @return Collection<int, Campaign>
     */
    public function getCampaignsByAllTags(array $tagIds): Collection
    {
        try {
            return $this->readRepository->getByAllTags($tagIds, ['category', 'tags']);
        } catch (\Exception $e) {
            Log::error('Failed to fetch campaigns by all tags', [
                'tag_ids' => $tagIds,
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Get campaigns for management view
     *
     * Returns campaigns based on user permissions:
     * - Users with 'manageAllCampaigns' permission: all campaigns
     * - Regular users: only their own campaigns
     *
     * This follows the Open/Closed Principle - new roles can be granted
     * the 'manageAllCampaigns' permission without modifying this code.
     *
     * @param \App\Models\Auth\User $user
     * @return Collection<int, Campaign>
     */
    public function getCampaignsForManagement(\App\Models\Auth\User $user): Collection
    {
        try {
            // If user has permission to manage all campaigns, return all
            if ($user->hasPermissionTo(CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value)) {
                return $this->readRepository->getAll();
            }

            // Otherwise, return only user's campaigns
            return $this->readRepository->getByCreator($user->id, ['category', 'tags']);
        } catch (\Exception $e) {
            Log::error('Failed to fetch campaigns for management', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Get total funds raised from active and completed campaigns
     *
     * Returns the sum of current_amount from campaigns that are:
     * - Status is ACTIVE or COMPLETED
     *
     * @return float
     */
    public function getTotalFundsRaised(): float
    {
        try {
            return $this->aggregateRepository->sumByStatus(
                'current_amount',
                [CampaignStatus::ACTIVE, CampaignStatus::COMPLETED]
            );
        } catch (\Exception $e) {
            Log::error('Failed to calculate total funds raised', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 0 on error
            return 0.0;
        }
    }

    /**
     * Get count of completed campaigns
     *
     * Returns the count of campaigns that have status COMPLETED
     *
     * @return int
     */
    public function getCompletedCampaignsCount(): int
    {
        try {
            return $this->aggregateRepository->countByStatus(CampaignStatus::COMPLETED);
        } catch (\Exception $e) {
            Log::error('Failed to count completed campaigns', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 0 on error
            return 0;
        }
    }

    /**
     * Get fundraising progress statistics
     *
     * Returns an array with:
     * - 'total_goal': sum of goal_amount from active/completed campaigns
     * - 'total_raised': sum of current_amount from active/completed campaigns
     * - 'percentage': percentage of goal achieved
     *
     * @return array{total_goal: float, total_raised: float, percentage: float}
     */
    public function getFundraisingProgress(): array
    {
        try {
            $data = $this->aggregateRepository->getAggregatedFundingData([
                CampaignStatus::ACTIVE,
                CampaignStatus::COMPLETED,
            ]);

            $totalGoal = $data['total_goal'];
            $totalRaised = $data['total_raised'];

            // Calculate percentage
            $percentage = $totalGoal > 0 ? ($totalRaised / $totalGoal) * 100 : 0;

            return [
                'total_goal' => $totalGoal,
                'total_raised' => $totalRaised,
                'percentage' => round($percentage, 2),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to calculate fundraising progress', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return zeros on error
            return [
                'total_goal' => 0.0,
                'total_raised' => 0.0,
                'percentage' => 0.0,
            ];
        }
    }
}
