<?php

declare(strict_types=1);

namespace App\Policies\Donation;

use App\Enums\Campaign\CampaignStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;

/**
 * Policy for managing donation authorization
 *
 * Defines authorization rules for donation-related actions
 */
class DonationPolicy
{
    /**
     * Determine if the user can create a donation for the campaign
     *
     * A user can create a donation only if:
     * - The campaign is in Active status
     *
     * @param Campaign $campaign The campaign to donate to
     * @return bool True if user can donate, false otherwise
     */
    public function donate(User $user, Campaign $campaign): bool
    {
        return $campaign->status === CampaignStatus::ACTIVE;
    }
}
