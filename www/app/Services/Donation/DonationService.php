<?php

declare(strict_types=1);

namespace App\Services\Donation;

use App\Models\Campaign\Campaign;
use App\Models\Auth\User;

/**
 * Service for managing donation business logic
 *
 * This service handles all business logic related to donations
 */
class DonationService
{
    /**
     * Get donation page data for a campaign
     *
     * @param Campaign $campaign The campaign to donate to
     * @return array<string, mixed> Campaign data for donation page
     */
    public function getDonationPageData(Campaign $campaign): array
    {
        return [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'description' => $campaign->description,
                'currency' => $campaign->currency?->value,
                'goal_amount' => $campaign->goal_amount,
                'current_amount' => $campaign->current_amount,
            ],
        ];
    }

    /**
     * Get quick donation amounts based on campaign currency
     *
     * @param Campaign $campaign The campaign
     * @return array<int, int> Quick donation amounts
     */
    public function getQuickDonationAmounts(Campaign $campaign): array
    {
        // Standard quick donation amounts
        return [5, 10, 20, 50, 100];
    }
}
