<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Campaign;

use App\Contracts\Campaign\CampaignQueryServiceInterface;
use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Mappers\Campaign\CampaignMapper;
use Illuminate\Http\JsonResponse;
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
    public function getActiveCampaignsCount(): JsonResponse
    {
        $count = $this->campaignQueryService->getActiveCampaignsCount();

        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Get campaigns for management
     *
     * Returns campaigns based on user role:
     * - Campaign managers and admins: all campaigns
     * - Regular users: only their own campaigns
     */
    public function getCampaignsForManagement(): AnonymousResourceCollection
    {
        /** @var \App\Models\Auth\User $user */
        $user = auth()->user();
        $campaigns = $this->campaignQueryService->getCampaignsForManagement($user);

        return CampaignResource::collection($campaigns);
    }

    /**
     * Get all campaigns
     */
    public function index(): AnonymousResourceCollection
    {
        $campaigns = $this->campaignQueryService->getAllCampaigns();

        return CampaignResource::collection($campaigns);
    }

    /**
     * Store a new campaign
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $dto = CampaignMapper::fromStoreRequest($request);
        $campaign = $this->campaignWriteService->createCampaign($dto);

        return response()->json([
            'message' => 'Campaign created successfully',
            'data' => new CampaignResource($campaign),
        ], 201);
    }

    /**
     * Display the specified campaign
     */
    public function show(string $id): JsonResponse
    {
        $campaign = $this->campaignQueryService->findById($id);

        if (!$campaign) {
            return response()->json([
                'message' => 'Campaign not found',
            ], 404);
        }

        // Load relationships for detailed view
        $campaign->load(['category', 'tags']);

        return response()->json([
            'data' => new CampaignResource($campaign),
        ]);
    }

    /**
     * Update the specified campaign
     */
    public function update(UpdateCampaignRequest $request, string $id): JsonResponse
    {
        try {
            $campaign = $this->campaignWriteService->updateCampaign($id, $request->validated());

            return response()->json([
                'message' => 'Campaign updated successfully',
                'data' => new CampaignResource($campaign),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Campaign not found',
            ], 404);
        }
    }

    /**
     * Remove the specified campaign
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->campaignWriteService->deleteCampaign($id);

            return response()->json([
                'message' => 'Campaign deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Campaign not found',
            ], 404);
        }
    }
}
