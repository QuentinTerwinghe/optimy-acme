<?php

declare(strict_types=1);

namespace App\Policies\Campaign;

use App\Enums\Campaign\CampaignPermissions;
use App\Enums\Campaign\CampaignStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;

/**
 * Campaign Policy
 *
 * Handles authorization for campaign-related actions.
 * Follows SOLID principles by separating authorization logic from controllers.
 */
class CampaignPolicy
{
    /**
     * Determine if the user can update the campaign.
     *
     * A user can edit a campaign if:
     * 1. They have 'manageAllCampaigns' permission (admins, campaign managers), OR
     * 2. They have 'editOwnCampaign' permission AND created the campaign
     *
     * AND the campaign status is either DRAFT or WAITING_FOR_VALIDATION
     */
    public function update(User $user, Campaign $campaign): bool
    {
        // Check if campaign status allows editing
        if (!$this->isEditableStatus($campaign)) {
            return false;
        }

        // Check if user can manage all campaigns
        if ($user->can(CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value)) {
            return true;
        }

        // Check if user can edit their own campaign and they created it
        return $user->can(CampaignPermissions::EDIT_OWN_CAMPAIGN->value)
            && $campaign->created_by === $user->id;
    }

    /**
     * Determine if the user can manage all campaigns.
     *
     * This is used for administrative actions like validating campaigns.
     */
    public function manageAllCampaigns(User $user): bool
    {
        return $user->can(CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value);
    }

    /**
     * Determine if the campaign status allows editing.
     *
     * Only DRAFT and WAITING_FOR_VALIDATION campaigns can be edited.
     */
    private function isEditableStatus(Campaign $campaign): bool
    {
        return in_array($campaign->status, [
            CampaignStatus::DRAFT,
            CampaignStatus::WAITING_FOR_VALIDATION,
        ], true);
    }
}
