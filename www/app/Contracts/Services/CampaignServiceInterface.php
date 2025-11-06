<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use Illuminate\Database\Eloquent\Collection;

/**
 * Campaign Service Interface
 *
 * Defines the contract for campaign-related business logic
 */
interface CampaignServiceInterface
{
    /**
     * Get all active campaigns
     *
     * @return Collection<int, \App\Models\Campaign>
     */
    public function getActiveCampaigns(): Collection;
}
