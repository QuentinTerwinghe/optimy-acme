<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

use App\Enums\Campaign\CampaignStatus;

/**
 * Campaign Aggregate Repository Interface
 *
 * Defines the contract for campaign aggregations and statistics
 * Follows Interface Segregation Principle - focused on aggregation operations only
 */
interface CampaignAggregateRepositoryInterface
{
    /**
     * Count campaigns by status
     *
     * @param CampaignStatus $status
     * @return int
     */
    public function countByStatus(CampaignStatus $status): int;

    /**
     * Count active campaigns
     *
     * @return int
     */
    public function countActiveCampaigns(): int;

    /**
     * Sum field by status
     *
     * @param string $field
     * @param array<int, CampaignStatus> $statuses
     * @return float
     */
    public function sumByStatus(string $field, array $statuses): float;

    /**
     * Get campaigns with aggregated data
     *
     * @param array<int, CampaignStatus> $statuses
     * @return array{total_goal: float, total_raised: float}
     */
    public function getAggregatedFundingData(array $statuses): array;
}
