<?php

declare(strict_types=1);

namespace App\Http\Controllers\Campaign;

use App\Contracts\Campaign\CampaignQueryServiceInterface;
use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Contracts\Category\CategoryQueryServiceInterface;
use App\Contracts\Tag\TagQueryServiceInterface;
use App\Enums\Campaign\CampaignStatus;
use App\Enums\Common\Currency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Mappers\Campaign\CampaignMapper;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CampaignController extends Controller
{
    use AuthorizesRequests;

    /**
     * Constructor
     *
     * @param CampaignQueryServiceInterface $campaignQueryService
     * @param CampaignWriteServiceInterface $campaignWriteService
     * @param CategoryQueryServiceInterface $categoryQueryService
     * @param TagQueryServiceInterface $tagQueryService
     */
    public function __construct(
        private readonly CampaignQueryServiceInterface $campaignQueryService,
        private readonly CampaignWriteServiceInterface $campaignWriteService,
        private readonly CategoryQueryServiceInterface $categoryQueryService,
        private readonly TagQueryServiceInterface $tagQueryService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('campaigns.manage');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $categories = $this->categoryQueryService->getActiveCategories();
        $tags = $this->tagQueryService->getAllTags();
        $currencies = Currency::cases();

        return view('campaigns.create', [
            'categories' => $categories,
            'tags' => $tags,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        try {
            // Convert request to DTO using mapper
            $dto = CampaignMapper::fromStoreRequest($request);

            // Create campaign using the service
            $campaign = $this->campaignWriteService->createCampaign($dto);

            // Refresh to ensure all attributes are properly loaded
            $campaign->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => $campaign->toArray(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): View|RedirectResponse
    {
        try {
            // Find the campaign by ID using the query service
            $campaign = $this->campaignQueryService->findByIdWithRelations($id);

            if (!$campaign) {
                return redirect()
                    ->route('dashboard')
                    ->with('error', 'Campaign not found.');
            }

            return view('campaigns.show', [
                'campaign' => $campaign,
            ]);
        } catch (\Exception $e) {
            // Handle invalid UUIDs or other errors
            return redirect()
                ->route('dashboard')
                ->with('error', 'Campaign not found.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View|RedirectResponse
    {
        try {
            // Find the campaign by ID and load tags relationship
            $campaign = $this->campaignQueryService->findByIdWithRelations($id, ['tags']);

            if (!$campaign) {
                abort(404, 'Campaign not found');
            }

            // Check authorization using the policy
            $this->authorize('update', $campaign);

            // Load necessary data for the form
            $categories = $this->categoryQueryService->getActiveCategories();
            $tags = $this->tagQueryService->getAllTags();
            $currencies = Currency::cases();

            return view('campaigns.edit', [
                'campaign' => $campaign,
                'categories' => $categories,
                'tags' => $tags,
                'currencies' => $currencies,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()
                ->route('campaigns.index')
                ->with('error', 'You are not authorized to edit this campaign or the campaign cannot be edited in its current status.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCampaignRequest $request, string $id): JsonResponse
    {
        try {
            // Find the campaign by UUID
            $campaign = $this->campaignQueryService->findById($id);

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign not found.',
                ], 404);
            }

            // Check authorization using the policy
            $this->authorize('update', $campaign);

            // Convert request to DTO using mapper
            $dto = CampaignMapper::fromUpdateRequest($request);

            if (!$dto->hasAnyField()) {
                throw new \InvalidArgumentException('At least one data is required to perform an update');
            }

            // Update campaign using the service
            $updatedCampaign = $this->campaignWriteService->updateCampaign((string) $campaign->id, $dto);

            // Refresh to ensure all attributes are properly loaded
            $updatedCampaign->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data' => $updatedCampaign->toArray(),
            ], 200);
        } catch (\InvalidArgumentException $e) {
            // Parse the exception message to extract field names and return validation error format
            $message = $e->getMessage();
            $errors = [];

            // Extract missing fields from the message
            if (str_contains($message, 'without required fields:')) {
                $fieldsString = substr($message, strpos($message, 'without required fields:') + 25);
                $fields = array_map('trim', explode(',', $fieldsString));

                foreach ($fields as $field) {
                    $fieldName = str_replace('_', ' ', $field);
                    $errors[$field] = [ucfirst($fieldName) . ' is required'];
                }
            }

            return response()->json([
                'message' => count($errors) > 1
                    ? array_keys($errors)[0] . ' is required (and ' . (count($errors) - 1) . ' more errors)'
                    : ($errors[array_key_first($errors)][0] ?? $message),
                'errors' => $errors,
            ], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this campaign.',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate a campaign (set status to active)
     * This endpoint ONLY changes the status, does not modify other fields
     */
    public function validate(string $id): JsonResponse
    {
        try {
            // Find the campaign by UUID
            $campaign = $this->campaignQueryService->findById($id);

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign not found.',
                ], 404);
            }

            // Check authorization using the policy
            $this->authorize('validate', $campaign);

            // Create DTO with only status change
            $dto = new \App\DTOs\Campaign\UpdateCampaignDTO(
                status: CampaignStatus::ACTIVE
            );

            // Update campaign using the service
            $updatedCampaign = $this->campaignWriteService->updateCampaign(
                (string) $campaign->id,
                $dto
            );

            // Refresh to ensure all attributes are properly loaded
            $updatedCampaign->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Campaign validated successfully',
                'data' => $updatedCampaign->toArray(),
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to validate campaigns.',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate campaign: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a campaign (set status to rejected)
     * This endpoint ONLY changes the status, does not modify other fields
     */
    public function reject(string $id): JsonResponse
    {
        try {
            // Find the campaign by UUID
            $campaign = $this->campaignQueryService->findById($id);

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign not found.',
                ], 404);
            }

            // Check authorization using the policy
            $this->authorize('reject', $campaign);

            // Create DTO with only status change
            $dto = new \App\DTOs\Campaign\UpdateCampaignDTO(
                status: CampaignStatus::REJECTED
            );

            // Update campaign using the service
            $updatedCampaign = $this->campaignWriteService->updateCampaign(
                (string) $campaign->id,
                $dto
            );

            // Refresh to ensure all attributes are properly loaded
            $updatedCampaign->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Campaign rejected successfully',
                'data' => $updatedCampaign->toArray(),
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject campaigns.',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject campaign: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): never
    {
        // TODO: Implement destroy method
        abort(404);
    }
}
