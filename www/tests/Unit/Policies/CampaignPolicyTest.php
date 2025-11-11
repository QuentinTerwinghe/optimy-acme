<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\Campaign\CampaignPermissions;
use App\Enums\Campaign\CampaignStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Policies\Campaign\CampaignPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CampaignPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CampaignPolicy $policy;
    private User $userWithEditOwn;
    private User $userWithManageAll;
    private User $userWithoutPermissions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CampaignPolicy();

        // Create permissions
        Permission::firstOrCreate(['name' => CampaignPermissions::EDIT_OWN_CAMPAIGN->value]);
        Permission::firstOrCreate(['name' => CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value]);

        // Create roles
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo(CampaignPermissions::EDIT_OWN_CAMPAIGN->value);

        $managerRole = Role::create(['name' => 'campaign_manager']);
        $managerRole->givePermissionTo(CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value);

        // Create users
        $this->userWithEditOwn = User::factory()->create();
        $this->userWithEditOwn->assignRole($userRole);

        $this->userWithManageAll = User::factory()->create();
        $this->userWithManageAll->assignRole($managerRole);

        $this->userWithoutPermissions = User::factory()->create();
    }

    /** @test */
    public function user_can_update_own_draft_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->update($this->userWithEditOwn, $campaign);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_can_update_own_waiting_for_validation_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->update($this->userWithEditOwn, $campaign);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_update_own_active_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->update($this->userWithEditOwn, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function user_cannot_update_own_completed_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->update($this->userWithEditOwn, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function user_cannot_update_own_cancelled_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::CANCELLED,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->update($this->userWithEditOwn, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function user_cannot_update_campaign_created_by_another_user(): void
    {
        $otherUser = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'created_by' => $otherUser->id,
        ]);

        $result = $this->policy->update($this->userWithEditOwn, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function manager_can_update_any_draft_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->update($this->userWithManageAll, $campaign);

        $this->assertTrue($result);
    }

    /** @test */
    public function manager_can_update_any_waiting_for_validation_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->update($this->userWithManageAll, $campaign);

        $this->assertTrue($result);
    }

    /** @test */
    public function manager_cannot_update_active_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->update($this->userWithManageAll, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function manager_cannot_update_completed_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->update($this->userWithManageAll, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function manager_cannot_update_cancelled_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::CANCELLED,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->update($this->userWithManageAll, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function user_without_permissions_cannot_update_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'created_by' => $this->userWithoutPermissions->id,
        ]);

        $result = $this->policy->update($this->userWithoutPermissions, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function user_with_edit_own_permission_but_not_creator_cannot_update(): void
    {
        $otherUser = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'created_by' => $otherUser->id,
        ]);

        $result = $this->policy->update($this->userWithEditOwn, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function manager_can_validate_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->validate($this->userWithManageAll, $campaign);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_without_manage_all_permission_cannot_validate_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->validate($this->userWithEditOwn, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function user_without_permissions_cannot_validate_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $this->userWithoutPermissions->id,
        ]);

        $result = $this->policy->validate($this->userWithoutPermissions, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function manager_can_reject_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->reject($this->userWithManageAll, $campaign);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_without_manage_all_permission_cannot_reject_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $this->userWithEditOwn->id,
        ]);

        $result = $this->policy->reject($this->userWithEditOwn, $campaign);

        $this->assertFalse($result);
    }

    /** @test */
    public function user_without_permissions_cannot_reject_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $this->userWithoutPermissions->id,
        ]);

        $result = $this->policy->reject($this->userWithoutPermissions, $campaign);

        $this->assertFalse($result);
    }
}
