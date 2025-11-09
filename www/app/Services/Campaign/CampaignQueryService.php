<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Contracts\Campaign\CampaignQueryServiceInterface;
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
 */
class CampaignQueryService implements CampaignQueryServiceInterface
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
    public function getActiveCampaigns(): Collection
    {
        try {
            return Campaign::query()
                ->where('status', CampaignStatus::ACTIVE)
                ->where('end_date', '>', now())
                ->orderBy('end_date', 'asc')
                ->get();
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
            return Campaign::query()
                ->where('status', CampaignStatus::ACTIVE)
                ->where('end_date', '>', now())
                ->count();
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
            return Campaign::find($id);
        } catch (\Exception $e) {
            Log::error('Failed to find campaign by ID', [
                'id' => $id,
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
            return Campaign::query()
                ->orderBy('created_at', 'desc')
                ->get();
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
            return Campaign::query()
                ->where('status', $status)
                ->orderBy('created_at', 'desc')
                ->get();
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
            return Campaign::query()
                ->where('category_id', $categoryId)
                ->with(['category', 'tags'])
                ->orderBy('created_at', 'desc')
                ->get();
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
            return Campaign::query()
                ->whereHas('tags', function ($query) use ($tagIds) {
                    $query->whereIn('tags.id', $tagIds);
                })
                ->with(['category', 'tags'])
                ->orderBy('created_at', 'desc')
                ->get();
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
            $tagCount = count($tagIds);

            return Campaign::query()
                ->whereHas('tags', function ($query) use ($tagIds) {
                    $query->whereIn('tags.id', $tagIds);
                }, '=', $tagCount)
                ->with(['category', 'tags'])
                ->orderBy('created_at', 'desc')
                ->get();
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
            $query = Campaign::query();

            // If user doesn't have permission to manage all campaigns, filter by created_by
            if (!$user->can(CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value)) {
                $query->where('created_by', $user->id);
            }

            return $query
                ->with(['category', 'tags'])
                ->orderBy('creation_date', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to fetch campaigns for management', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }
}
