<?php

declare(strict_types=1);

namespace App\Http\Controllers\Campaign;

use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Enums\CampaignStatus;
use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use App\Models\Campaign\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Constructor
     *
     * @param CampaignWriteServiceInterface $campaignWriteService
     */
    public function __construct(
        private CampaignWriteServiceInterface $campaignWriteService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // TODO: Implement index method
        abort(404);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        $tags = Tag::orderBy('name')->get();

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
            $data = $request->validated();

            // Map frontend status values to CampaignStatus enum values
            $statusMapping = [
                'draft' => CampaignStatus::DRAFT->value,
                'waiting_for_validation' => CampaignStatus::WAITING_FOR_VALIDATION->value,
            ];

            // Get status from request or default to DRAFT
            $requestedStatus = $data['status'] ?? 'draft';
            $data['status'] = $statusMapping[$requestedStatus] ?? CampaignStatus::DRAFT->value;

            // Set current_amount to 0 if not provided
            if (!isset($data['current_amount'])) {
                $data['current_amount'] = 0;
            }

            // Create campaign using the service
            $campaign = $this->campaignWriteService->createCampaign($data);

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
    public function show(string $id): never
    {
        // TODO: Implement show method
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): never
    {
        // TODO: Implement edit method
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): never
    {
        // TODO: Implement update method
        abort(404);
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
