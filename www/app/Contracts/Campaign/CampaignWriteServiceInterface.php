<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

use App\DTOs\Campaign\CampaignDTO;
use App\DTOs\Campaign\UpdateCampaignDTO;
use App\Models\Campaign\Campaign;

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
     * @param CampaignDTO $dto
     * @return Campaign
     * @throws \Exception
     */
    public function createCampaign(CampaignDTO $dto): Campaign;

    /**
     * Update an existing campaign
     *
     * @param string $id
     * @param UpdateCampaignDTO $dto
     * @return Campaign
     * @throws \Exception
     */
    public function updateCampaign(string $id, UpdateCampaignDTO $dto): Campaign;

    /**
     * Delete a campaign
     *
     * @param string $id
     * @return bool
     * @throws \Exception
     */
    public function deleteCampaign(string $id): bool;
}
