<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

use App\Models\Campaign\Campaign;

/**
 * Campaign Write Repository Interface
 *
 * Defines the contract for writing campaign data
 * Follows Interface Segregation Principle - focused on write operations only
 */
interface CampaignWriteRepositoryInterface
{
    /**
     * Create a new campaign
     *
     * @param array<string, mixed> $data
     * @return Campaign
     */
    public function create(array $data): Campaign;

    /**
     * Update a campaign
     *
     * @param Campaign $campaign
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(Campaign $campaign, array $data): bool;

    /**
     * Delete a campaign
     *
     * @param Campaign $campaign
     * @return bool
     */
    public function delete(Campaign $campaign): bool;
}
