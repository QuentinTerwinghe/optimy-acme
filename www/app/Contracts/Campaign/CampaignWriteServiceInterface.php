<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

use App\Models\Campaign;

/**
 * Campaign Write Service Interface
 *
 * Defines the contract for campaign write operations (create, update, delete)
 * Follows Single Responsibility Principle - handles only writes
 */
interface CampaignWriteServiceInterface
{
    /**
     * Create a new campaign
     *
     * @param array<string, mixed> $data
     * @return Campaign
     * @throws \Exception
     */
    public function createCampaign(array $data): Campaign;

    /**
     * Update an existing campaign
     *
     * @param string $id
     * @param array<string, mixed> $data
     * @return Campaign
     * @throws \Exception
     */
    public function updateCampaign(string $id, array $data): Campaign;

    /**
     * Delete a campaign
     *
     * @param string $id
     * @return bool
     * @throws \Exception
     */
    public function deleteCampaign(string $id): bool;
}
