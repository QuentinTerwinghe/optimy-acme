<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Services\CampaignServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Campaign API Controller
 *
 * Handles HTTP requests for campaign-related operations
 * Follows Single Responsibility Principle - only handles HTTP layer
 * Business logic is delegated to CampaignService
 */
class CampaignController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        private readonly CampaignServiceInterface $campaignService
    ) {}

    /**
     * Get all active campaigns
     *
     * Returns a list of campaigns that are currently active and not yet ended
     */
    public function getActiveCampaigns(): AnonymousResourceCollection
    {
        $campaigns = $this->campaignService->getActiveCampaigns();

        return CampaignResource::collection($campaigns);
    }
}
