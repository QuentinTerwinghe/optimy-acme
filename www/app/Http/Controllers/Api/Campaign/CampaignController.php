<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Campaign;

use App\Contracts\Campaign\CampaignQueryServiceInterface;
use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Contracts\Category\CategoryQueryServiceInterface;
use App\Contracts\Tag\TagQueryServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Mappers\Campaign\CampaignMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

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
        private readonly CampaignWriteServiceInterface $campaignWriteService,
        private readonly CategoryQueryServiceInterface $categoryQueryService,
        private readonly TagQueryServiceInterface $tagQueryService
    ) {}

    /**
     * Get all active campaigns
     *
     * Returns a list of campaigns that are currently active and not yet ended
     * Supports filtering by search, category_id, and tag_ids
     */
    public function getActiveCampaigns(): AnonymousResourceCollection
    {
        $filters = [];

        // Get search query
        if (request()->has('search') && !empty(request()->input('search'))) {
            $filters['search'] = request()->input('search');
        }

        // Get category filter
        if (request()->has('category_id') && !empty(request()->input('category_id'))) {
            $filters['category_id'] = (int) request()->input('category_id');
        }

        // Get tags filter
        if (request()->has('tag_ids') && !empty(request()->input('tag_ids'))) {
            $tagIds = request()->input('tag_ids');
            // Handle both array and comma-separated string
            if (is_string($tagIds)) {
                $tagIds = explode(',', $tagIds);
            }
            $filters['tag_ids'] = array_map('intval', array_filter($tagIds));
        }

        Log::info('Active campaigns request', [
            'raw_params' => request()->all(),
            'filters' => $filters,
        ]);

        $campaigns = $this->campaignQueryService->getActiveCampaigns($filters);

        Log::info('Active campaigns result', [
            'count' => $campaigns->count(),
        ]);

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
     * Get total funds raised from active and completed campaigns
     *
     * Returns the sum of current_amount from campaigns with active or completed status
     */
    public function getTotalFundsRaised(): JsonResponse
    {
        $total = $this->campaignQueryService->getTotalFundsRaised();

        return response()->json([
            'total' => $total,
        ]);
    }

    /**
     * Get count of completed campaigns
     *
     * Returns the count of campaigns with completed status
     */
    public function getCompletedCampaignsCount(): JsonResponse
    {
        $count = $this->campaignQueryService->getCompletedCampaignsCount();

        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Get fundraising progress statistics
     *
     * Returns total goal vs total raised amounts with percentage
     */
    public function getFundraisingProgress(): JsonResponse
    {
        $progress = $this->campaignQueryService->getFundraisingProgress();

        return response()->json($progress);
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
            // Convert request to DTO using mapper
            $dto = \App\Mappers\Campaign\CampaignMapper::fromUpdateRequest($request);

            $campaign = $this->campaignWriteService->updateCampaign($id, $dto);

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

    /**
     * Get all active categories
     *
     * Returns categories that are currently active
     */
    public function getCategories(): JsonResponse
    {
        $categories = $this->categoryQueryService->getActiveCategories()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ]);

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Get all tags
     *
     * Returns all tags
     */
    public function getTags(): JsonResponse
    {
        $tags = $this->tagQueryService->getAllTags()
            ->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
            ]);

        return response()->json([
            'data' => $tags,
        ]);
    }
}
