<?php

declare(strict_types=1);

namespace App\Http\Controllers\Donation;

use App\Contracts\Campaign\CampaignReadRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Policies\Donation\DonationPolicy;
use App\Services\Donation\DonationService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

/**
 * Controller for handling donation-related HTTP requests
 *
 * This controller is thin and delegates business logic to the DonationService
 */
class DonationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Constructor - Inject all dependencies
     *
     * @param DonationService $donationService Service for business logic
     * @param CampaignReadRepositoryInterface $campaignRepository Repository for data access
     * @param DonationPolicy $donationPolicy Policy for authorization
     */
    public function __construct(
        private readonly DonationService $donationService,
        private readonly CampaignReadRepositoryInterface $campaignRepository,
        private readonly DonationPolicy $donationPolicy
    ) {}

    /**
     * Show the donation creation form for a campaign
     *
     * @param string $campaignId The campaign UUID
     * @return View|RedirectResponse
     */
    public function create(string $campaignId): View|RedirectResponse
    {
        try {
            $campaign = $this->campaignRepository->findWithRelations(
                $campaignId,
                ['creator', 'category', 'tags']
            );

            if (!$campaign) {
                return redirect()
                    ->route('dashboard')
                    ->with('error', 'Campaign not found.');
            }

            // Get authenticated user
            if (empty($user = auth()->user())) {
                return redirect()->route('login.form');
            }

            // Authorize: check if user can create a donation using injected policy
            if (!$this->donationPolicy->create($campaign)) {
                return redirect()
                    ->route('campaigns.show', $campaignId)
                    ->with('error', 'This campaign is not accepting donations at this time.');
            }

            // Get donation page data from service
            $campaignData = $this->donationService->getDonationPageData($campaign);
            $quickAmounts = $this->donationService->getQuickDonationAmounts($campaign);

            return view('donations.create', [
                'campaign' => $campaign,
                'campaignData' => $campaignData,
                'quickAmounts' => $quickAmounts,
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'An error occurred while loading the donation page.');
        }
    }
}
