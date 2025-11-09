<?php

declare(strict_types=1);

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\CampaignPermissions;
use App\Enums\Campaign\CampaignStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CampaignEditTest extends TestCase
{
    use RefreshDatabase;

    private User $regularUser;
    private User $campaignManager;
    private Campaign $draftCampaign;
    private Campaign $waitingCampaign;
    private Campaign $activeCampaign;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::firstOrCreate(['name' => CampaignPermissions::EDIT_OWN_CAMPAIGN->value]);
        Permission::firstOrCreate(['name' => CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value]);

        // Create roles
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo(CampaignPermissions::EDIT_OWN_CAMPAIGN->value);

        $managerRole = Role::create(['name' => 'campaign_manager']);
        $managerRole->givePermissionTo(CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value);

        // Create users
        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole($userRole);

        $this->campaignManager = User::factory()->create();
        $this->campaignManager->assignRole($managerRole);

        // Create category
        $this->category = Category::factory()->create(['is_active' => true]);

        // Create campaigns with different statuses owned by regular user
        $this->draftCampaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);

        $this->waitingCampaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);

        $this->activeCampaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);
    }

    /** @test */
    public function user_can_edit_own_draft_campaign(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('campaigns.edit', ['id' => $this->draftCampaign->id]));

        $response->assertStatus(200);
        $response->assertViewIs('campaigns.edit');
        $response->assertViewHas('campaign');
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
        $response->assertViewHas('currencies');
    }

    /** @test */
    public function user_can_edit_own_waiting_for_validation_campaign(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('campaigns.edit', ['id' => $this->waitingCampaign->id]));

        $response->assertStatus(200);
        $response->assertViewIs('campaigns.edit');
        $response->assertViewHas('campaign');
    }

    /** @test */
    public function user_cannot_edit_own_active_campaign(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('campaigns.edit', ['id' => $this->activeCampaign->id]));

        $response->assertRedirect(route('campaigns.index'));
        $response->assertSessionHas('error', 'You are not authorized to edit this campaign or the campaign cannot be edited in its current status.');
    }

    /** @test */
    public function user_cannot_edit_campaign_created_by_another_user(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');

        $otherCampaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'created_by' => $otherUser->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('campaigns.edit', ['id' => $otherCampaign->id]));

        $response->assertRedirect(route('campaigns.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function campaign_manager_can_edit_any_draft_campaign(): void
    {
        $response = $this->actingAs($this->campaignManager)
            ->get(route('campaigns.edit', ['id' => $this->draftCampaign->id]));

        $response->assertStatus(200);
        $response->assertViewIs('campaigns.edit');
        $response->assertViewHas('campaign');
    }

    /** @test */
    public function campaign_manager_can_edit_any_waiting_for_validation_campaign(): void
    {
        $response = $this->actingAs($this->campaignManager)
            ->get(route('campaigns.edit', ['id' => $this->waitingCampaign->id]));

        $response->assertStatus(200);
        $response->assertViewIs('campaigns.edit');
        $response->assertViewHas('campaign');
    }

    /** @test */
    public function campaign_manager_cannot_edit_active_campaign(): void
    {
        $response = $this->actingAs($this->campaignManager)
            ->get(route('campaigns.edit', ['id' => $this->activeCampaign->id]));

        $response->assertRedirect(route('campaigns.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function guest_user_cannot_access_edit_page(): void
    {
        $response = $this->get(route('campaigns.edit', ['id' => $this->draftCampaign->id]));

        $response->assertRedirect(route('login.form'));
    }

    /** @test */
    public function edit_page_returns_404_for_nonexistent_campaign(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('campaigns.edit', ['id' => '00000000-0000-0000-0000-000000000000']))
            ->assertStatus(404);
    }

    /** @test */
    public function user_cannot_edit_completed_campaign(): void
    {
        $completedCampaign = Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('campaigns.edit', ['id' => $completedCampaign->id]));

        $response->assertRedirect(route('campaigns.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function user_cannot_edit_cancelled_campaign(): void
    {
        $cancelledCampaign = Campaign::factory()->create([
            'status' => CampaignStatus::CANCELLED,
            'created_by' => $this->regularUser->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('campaigns.edit', ['id' => $cancelledCampaign->id]));

        $response->assertRedirect(route('campaigns.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function edit_page_loads_campaign_data_correctly(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('campaigns.edit', ['id' => $this->draftCampaign->id]));

        $response->assertStatus(200);

        // Verify campaign data is passed to the view
        $viewCampaign = $response->viewData('campaign');
        $this->assertEquals($this->draftCampaign->id, $viewCampaign->id);
        $this->assertEquals($this->draftCampaign->title, $viewCampaign->title);
        $this->assertEquals($this->draftCampaign->description, $viewCampaign->description);
    }

    /** @test */
    public function edit_page_loads_all_active_categories(): void
    {
        // Create additional categories
        Category::factory()->count(3)->create(['is_active' => true]);
        Category::factory()->count(2)->create(['is_active' => false]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('campaigns.edit', ['id' => $this->draftCampaign->id]));

        $response->assertStatus(200);

        // Verify only active categories are loaded
        $categories = $response->viewData('categories');
        $this->assertCount(4, $categories); // 1 from setUp + 3 created here
    }

    /** @test */
    public function user_without_edit_permission_cannot_edit_campaign(): void
    {
        // Create a user without any permissions
        $userWithoutPermissions = User::factory()->create();

        $response = $this->actingAs($userWithoutPermissions)
            ->get(route('campaigns.edit', ['id' => $this->draftCampaign->id]));

        $response->assertRedirect(route('campaigns.index'));
        $response->assertSessionHas('error');
    }
}
