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

    /**
     * Recalculate the total amount of a campaign based on all successful donations
     *
     * This method ensures data consistency by recalculating the current_amount
     * from scratch rather than incrementing, which prevents amount drift and
     * handles edge cases like refunds or donation status changes.
     *
     * @param Campaign $campaign The campaign to recalculate
     * @return bool True if update was successful
     */
    public function recalculateTotalAmount(Campaign $campaign): bool;
}
