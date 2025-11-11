<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

/**
 * Campaign Statistics Interface
 *
 * Defines the contract for campaign statistics and aggregations
 * Follows Interface Segregation Principle - focused on statistics operations
 */
interface CampaignStatisticsInterface
{
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
     * Get total funds raised from active and completed campaigns
     *
     * Returns the sum of current_amount from campaigns that are:
     * - Status is ACTIVE or COMPLETED
     *
     * @return float
     */
    public function getTotalFundsRaised(): float;

    /**
     * Get count of completed campaigns
     *
     * Returns the count of campaigns that have status COMPLETED
     *
     * @return int
     */
    public function getCompletedCampaignsCount(): int;

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
    public function getFundraisingProgress(): array;
}
