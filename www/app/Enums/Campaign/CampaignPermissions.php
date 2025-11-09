<?php

declare(strict_types=1);

namespace App\Enums\Campaign;

/**
 * Campaign Permissions Enum
 *
 * Defines all permissions related to campaign management.
 * This provides type safety and prevents typos when checking permissions.
 *
 * Usage:
 * - In code: $user->can(CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value)
 * - In seeders: Permission::create(['name' => CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value])
 * - In tests: $role->givePermissionTo(CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value)
 */
enum CampaignPermissions: string
{
    /**
     * Can view and manage all campaigns regardless of creator
     * Typically assigned to: admin, campaign_manager
     */
    case MANAGE_ALL_CAMPAIGNS = 'manageAllCampaigns';

    /**
     * Can create new campaigns
     * Typically assigned to: all authenticated users
     */
    case CREATE_CAMPAIGN = 'createCampaign';

    /**
     * Can edit own campaigns
     * Typically assigned to: all authenticated users
     */
    case EDIT_OWN_CAMPAIGN = 'editOwnCampaign';

    /**
     * Can delete own campaigns
     * Typically assigned to: all authenticated users
     */
    case DELETE_OWN_CAMPAIGN = 'deleteOwnCampaign';

    /**
     * Can view campaigns
     * Typically assigned to: all authenticated users
     */
    case VIEW_CAMPAIGNS = 'viewCampaigns';

    /**
     * Get human-readable description of the permission
     */
    public function description(): string
    {
        return match ($this) {
            self::MANAGE_ALL_CAMPAIGNS => 'Can view and manage all campaigns regardless of creator',
            self::CREATE_CAMPAIGN => 'Can create new campaigns',
            self::EDIT_OWN_CAMPAIGN => 'Can edit own campaigns',
            self::DELETE_OWN_CAMPAIGN => 'Can delete own campaigns',
            self::VIEW_CAMPAIGNS => 'Can view campaigns',
        };
    }

    /**
     * Get all permission values as an array
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn(self $permission) => $permission->value, self::cases());
    }

    /**
     * Get all permission values for campaign managers
     * (users who can manage all campaigns)
     *
     * @return array<int, string>
     */
    public static function campaignManagerPermissions(): array
    {
        return [
            self::MANAGE_ALL_CAMPAIGNS->value,
            self::CREATE_CAMPAIGN->value,
            self::EDIT_OWN_CAMPAIGN->value,
            self::DELETE_OWN_CAMPAIGN->value,
            self::VIEW_CAMPAIGNS->value,
        ];
    }

    /**
     * Get all permission values for regular users
     * (users who can only manage their own campaigns)
     *
     * @return array<int, string>
     */
    public static function regularUserPermissions(): array
    {
        return [
            self::CREATE_CAMPAIGN->value,
            self::EDIT_OWN_CAMPAIGN->value,
            self::DELETE_OWN_CAMPAIGN->value,
            self::VIEW_CAMPAIGNS->value,
        ];
    }
}
