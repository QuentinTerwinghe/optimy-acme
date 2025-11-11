<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\Campaign\CampaignPermissions;
use App\Enums\Campaign\CampaignStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use App\Services\Campaign\CampaignQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CampaignQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CampaignQueryService $service;
    private User $regularUser;
    private User $campaignManager;
    private User $admin;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Use app() to resolve the service with all dependencies
        $this->service = app(\App\Contracts\Campaign\CampaignQueryServiceInterface::class);

        // Create permissions
        $manageAllCampaignsPermission = Permission::firstOrCreate([
            'name' => CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value
        ]);

        // Create wildcard permission for admin
        $wildcardPermission = Permission::firstOrCreate([
            'name' => '*'
        ]);

        // Create roles
        $campaignManagerRole = Role::create(['name' => 'campaign_manager']);
        $adminRole = Role::create(['name' => 'admin']);

        // Assign permissions to roles
        $campaignManagerRole->givePermissionTo($manageAllCampaignsPermission);
        // Admin gets wildcard permission (which should grant all permissions)
        $adminRole->givePermissionTo($wildcardPermission);
        // Also explicitly give manageAllCampaigns for clarity (as done in RoleSeeder)
        $adminRole->givePermissionTo($manageAllCampaignsPermission);

        // Create test users
        $this->regularUser = User::factory()->create();
        $this->campaignManager = User::factory()->create();
        $this->campaignManager->assignRole('campaign_manager');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create test category
        $this->category = Category::factory()->create([
            'is_active' => true,
        ]);
    }

    public function test_regular_user_only_sees_own_campaigns(): void
    {
        // Create campaigns by regular user
        $userCampaigns = Campaign::factory()->count(3)->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);

        // Create campaigns by another user
        $otherUser = User::factory()->create();
        Campaign::factory()->count(2)->create([
            'created_by' => $otherUser->id,
            'category_id' => $this->category->id,
        ]);

        $result = $this->service->getCampaignsForManagement($this->regularUser);

        $this->assertCount(3, $result);
        foreach ($result as $campaign) {
            $this->assertEquals($this->regularUser->id, $campaign->created_by);
        }
    }

    public function test_campaign_manager_sees_all_campaigns(): void
    {
        // Create campaigns by different users
        Campaign::factory()->count(3)->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);

        $otherUser = User::factory()->create();
        Campaign::factory()->count(2)->create([
            'created_by' => $otherUser->id,
            'category_id' => $this->category->id,
        ]);

        $result = $this->service->getCampaignsForManagement($this->campaignManager);

        $this->assertCount(5, $result);
    }

    public function test_admin_sees_all_campaigns(): void
    {
        // Create campaigns by different users
        Campaign::factory()->count(3)->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);

        $otherUser = User::factory()->create();
        Campaign::factory()->count(2)->create([
            'created_by' => $otherUser->id,
            'category_id' => $this->category->id,
        ]);

        $result = $this->service->getCampaignsForManagement($this->admin);

        $this->assertCount(5, $result);
    }

    public function test_campaigns_for_management_includes_relationships(): void
    {
        $campaign = Campaign::factory()->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);

        $result = $this->service->getCampaignsForManagement($this->regularUser);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->relationLoaded('category'));
        $this->assertTrue($result->first()->relationLoaded('tags'));
    }

    public function test_campaigns_for_management_ordered_by_created_at_desc(): void
    {
        $oldCampaign = Campaign::factory()->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
            'title' => 'Old Campaign',
            'created_at' => now()->subDays(5),
        ]);

        $newCampaign = Campaign::factory()->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
            'title' => 'New Campaign',
            'created_at' => now(),
        ]);

        $result = $this->service->getCampaignsForManagement($this->regularUser);

        $this->assertEquals('New Campaign', $result->first()->title);
        $this->assertEquals('Old Campaign', $result->last()->title);
    }

    public function test_get_campaigns_for_management_returns_empty_collection_on_error(): void
    {
        // Create a user with an invalid ID to trigger an error
        $invalidUser = new User();
        $invalidUser->id = 999999;

        $result = $this->service->getCampaignsForManagement($invalidUser);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_campaigns_for_management_includes_all_statuses(): void
    {
        // Create campaigns with different statuses
        Campaign::factory()->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
            'status' => CampaignStatus::DRAFT,
        ]);

        Campaign::factory()->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
            'status' => CampaignStatus::ACTIVE,
        ]);

        Campaign::factory()->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
            'status' => CampaignStatus::COMPLETED,
        ]);

        $result = $this->service->getCampaignsForManagement($this->regularUser);

        $this->assertCount(3, $result);

        $statuses = $result->pluck('status')->map(fn($status) => $status->value)->toArray();
        $this->assertContains('draft', $statuses);
        $this->assertContains('active', $statuses);
        $this->assertContains('completed', $statuses);
    }

    public function test_new_role_with_permission_can_see_all_campaigns(): void
    {
        // Create a new role that doesn't exist yet
        $supervisorRole = Role::create(['name' => 'supervisor']);

        // Grant the manageAllCampaigns permission to this new role
        $manageAllCampaignsPermission = Permission::where(
            'name',
            CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value
        )->first();
        $supervisorRole->givePermissionTo($manageAllCampaignsPermission);

        // Create a supervisor user
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        // Create campaigns by different users
        Campaign::factory()->count(3)->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);

        $otherUser = User::factory()->create();
        Campaign::factory()->count(2)->create([
            'created_by' => $otherUser->id,
            'category_id' => $this->category->id,
        ]);

        // The supervisor should see all campaigns without any code changes
        $result = $this->service->getCampaignsForManagement($supervisor);

        $this->assertCount(5, $result);
    }

    public function test_user_with_wildcard_permission_sees_all_campaigns(): void
    {
        // Create a new role with ONLY wildcard permission (no explicit manageAllCampaigns)
        $superAdminRole = Role::create(['name' => 'super_admin']);

        // Get wildcard permission
        $wildcardPermission = Permission::where('name', '*')->first();
        $superAdminRole->givePermissionTo($wildcardPermission);

        // Create a super admin user
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // Create campaigns by different users
        Campaign::factory()->count(3)->create([
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);

        $otherUser = User::factory()->create();
        Campaign::factory()->count(2)->create([
            'created_by' => $otherUser->id,
            'category_id' => $this->category->id,
        ]);

        // Verify that wildcard permission check works
        $this->assertTrue($superAdmin->hasPermissionTo('*'));
        $this->assertTrue($superAdmin->hasPermissionTo(CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value));

        // The super admin should see all campaigns thanks to wildcard permission
        $result = $this->service->getCampaignsForManagement($superAdmin);

        $this->assertCount(5, $result);
    }
}
