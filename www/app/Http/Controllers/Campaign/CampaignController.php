<?php

declare(strict_types=1);

namespace App\Http\Controllers\Campaign;

use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Enums\Common\Currency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Mappers\Campaign\CampaignMapper;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use App\Models\Campaign\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        return view('campaigns.manage');
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
    public function show(string $id): never
    {
        // TODO: Implement show method
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View|RedirectResponse
    {
        // Find the campaign by ID and load tags relationship
        $campaign = Campaign::with('tags')->findByUuid($id)->firstOrFail();

        // Get current authenticated user
        $user = auth()->user();

        // Check authorization using the policy
        if ($user === null || !$user->can('update', $campaign)) {
            // Redirect to campaigns list if unauthorized
            return redirect()
                ->route('campaigns.index')
                ->with('error', 'You are not authorized to edit this campaign or the campaign cannot be edited in its current status.');
        }

        // Load necessary data for the form
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        $tags = Tag::orderBy('name')->get();

        $currencies = Currency::cases();

        return view('campaigns.edit', [
            'campaign' => $campaign,
            'categories' => $categories,
            'tags' => $tags,
            'currencies' => $currencies,
        ]);
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
