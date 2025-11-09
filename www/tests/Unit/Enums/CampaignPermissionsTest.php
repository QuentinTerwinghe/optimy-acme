<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Campaign\CampaignPermissions;
use Tests\TestCase;

class CampaignPermissionsTest extends TestCase
{
    public function test_has_all_expected_cases(): void
    {
        $cases = CampaignPermissions::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(CampaignPermissions::MANAGE_ALL_CAMPAIGNS, $cases);
        $this->assertContains(CampaignPermissions::CREATE_CAMPAIGN, $cases);
        $this->assertContains(CampaignPermissions::EDIT_OWN_CAMPAIGN, $cases);
        $this->assertContains(CampaignPermissions::DELETE_OWN_CAMPAIGN, $cases);
        $this->assertContains(CampaignPermissions::VIEW_CAMPAIGNS, $cases);
    }

    public function test_manage_all_campaigns_has_correct_value(): void
    {
        $this->assertEquals('manageAllCampaigns', CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value);
    }

    public function test_create_campaign_has_correct_value(): void
    {
        $this->assertEquals('createCampaign', CampaignPermissions::CREATE_CAMPAIGN->value);
    }

    public function test_edit_own_campaign_has_correct_value(): void
    {
        $this->assertEquals('editOwnCampaign', CampaignPermissions::EDIT_OWN_CAMPAIGN->value);
    }

    public function test_delete_own_campaign_has_correct_value(): void
    {
        $this->assertEquals('deleteOwnCampaign', CampaignPermissions::DELETE_OWN_CAMPAIGN->value);
    }

    public function test_view_campaigns_has_correct_value(): void
    {
        $this->assertEquals('viewCampaigns', CampaignPermissions::VIEW_CAMPAIGNS->value);
    }

    public function test_values_returns_all_permission_values(): void
    {
        $values = CampaignPermissions::values();

        $this->assertCount(5, $values);
        $this->assertContains('manageAllCampaigns', $values);
        $this->assertContains('createCampaign', $values);
        $this->assertContains('editOwnCampaign', $values);
        $this->assertContains('deleteOwnCampaign', $values);
        $this->assertContains('viewCampaigns', $values);
    }

    public function test_campaign_manager_permissions_includes_all_permissions(): void
    {
        $permissions = CampaignPermissions::campaignManagerPermissions();

        $this->assertCount(5, $permissions);
        $this->assertContains('manageAllCampaigns', $permissions);
        $this->assertContains('createCampaign', $permissions);
        $this->assertContains('editOwnCampaign', $permissions);
        $this->assertContains('deleteOwnCampaign', $permissions);
        $this->assertContains('viewCampaigns', $permissions);
    }

    public function test_regular_user_permissions_excludes_manage_all(): void
    {
        $permissions = CampaignPermissions::regularUserPermissions();

        $this->assertCount(4, $permissions);
        $this->assertNotContains('manageAllCampaigns', $permissions);
        $this->assertContains('createCampaign', $permissions);
        $this->assertContains('editOwnCampaign', $permissions);
        $this->assertContains('deleteOwnCampaign', $permissions);
        $this->assertContains('viewCampaigns', $permissions);
    }

    public function test_all_cases_have_descriptions(): void
    {
        foreach (CampaignPermissions::cases() as $permission) {
            $description = $permission->description();
            $this->assertNotEmpty($description);
            $this->assertIsString($description);
        }
    }

    public function test_manage_all_campaigns_description(): void
    {
        $description = CampaignPermissions::MANAGE_ALL_CAMPAIGNS->description();
        $this->assertEquals('Can view and manage all campaigns regardless of creator', $description);
    }

    public function test_create_campaign_description(): void
    {
        $description = CampaignPermissions::CREATE_CAMPAIGN->description();
        $this->assertEquals('Can create new campaigns', $description);
    }

    public function test_edit_own_campaign_description(): void
    {
        $description = CampaignPermissions::EDIT_OWN_CAMPAIGN->description();
        $this->assertEquals('Can edit own campaigns', $description);
    }

    public function test_delete_own_campaign_description(): void
    {
        $description = CampaignPermissions::DELETE_OWN_CAMPAIGN->description();
        $this->assertEquals('Can delete own campaigns', $description);
    }

    public function test_view_campaigns_description(): void
    {
        $description = CampaignPermissions::VIEW_CAMPAIGNS->description();
        $this->assertEquals('Can view campaigns', $description);
    }

    public function test_can_be_created_from_string_value(): void
    {
        $permission = CampaignPermissions::from('manageAllCampaigns');
        $this->assertEquals(CampaignPermissions::MANAGE_ALL_CAMPAIGNS, $permission);
    }

    public function test_all_cases_have_unique_values(): void
    {
        $values = array_map(fn($case) => $case->value, CampaignPermissions::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($values), $uniqueValues);
    }

    public function test_is_backed_by_string(): void
    {
        $reflection = new \ReflectionEnum(CampaignPermissions::class);
        $this->assertTrue($reflection->isBacked());
        $this->assertEquals('string', $reflection->getBackingType()->getName());
    }
}
