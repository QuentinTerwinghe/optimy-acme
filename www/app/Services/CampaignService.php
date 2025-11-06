<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\CampaignServiceInterface;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Campaign Service
 *
 * Handles all business logic related to campaigns
 * Follows Single Responsibility Principle by focusing only on campaign operations
 */
class CampaignService implements CampaignServiceInterface
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
}
