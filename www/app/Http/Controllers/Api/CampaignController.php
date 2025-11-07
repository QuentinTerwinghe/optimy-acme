<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Campaign\CampaignQueryServiceInterface;
use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Campaign API Controller
 *
 * Handles HTTP requests for campaign-related operations
 * Follows Single Responsibility Principle - only handles HTTP layer
 * Business logic is delegated to specialized Campaign services
 */
class CampaignController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        private readonly CampaignQueryServiceInterface $campaignQueryService,
        /** @phpstan-ignore-next-line property.onlyWritten - Write service will be used for create/update/delete endpoints */
        private readonly CampaignWriteServiceInterface $campaignWriteService
    ) {}

    /**
     * Get all active campaigns
     *
     * Returns a list of campaigns that are currently active and not yet ended
     */
    public function getActiveCampaigns(): AnonymousResourceCollection
    {
        $campaigns = $this->campaignQueryService->getActiveCampaigns();

        return CampaignResource::collection($campaigns);
    }

    /**
     * Get count of active campaigns
     *
     * Returns the count of campaigns that are currently active and not yet ended
     */
    public function getActiveCampaignsCount(): \Illuminate\Http\JsonResponse
    {
        $count = $this->campaignQueryService->getActiveCampaignsCount();

        return response()->json([
            'count' => $count,
        ]);
    }
}
