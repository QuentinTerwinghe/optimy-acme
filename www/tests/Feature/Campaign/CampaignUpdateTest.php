<?php

declare(strict_types=1);

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\CampaignPermissions;
use App\Enums\Campaign\CampaignStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use App\Models\Campaign\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CampaignUpdateTest extends TestCase
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
    public function user_can_update_own_draft_campaign_with_draft_status(): void
    {
        $updateData = [
            'title' => 'Updated Campaign Title',
            'description' => 'Updated description',
            'status' => CampaignStatus::DRAFT->value,
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('campaigns.update', ['id' => $this->draftCampaign->id]), $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Campaign updated successfully',
        ]);

        // Verify database update
        $this->assertDatabaseHas('campaigns', [
            'id' => $this->draftCampaign->id,
            'title' => 'Updated Campaign Title',
            'description' => 'Updated description',
            'status' => CampaignStatus::DRAFT->value,
        ]);
    }

    /** @test */
    public function user_can_update_own_draft_campaign_to_waiting_for_validation(): void
    {
        $updateData = [
            'title' => 'Updated Campaign Title',
            'goal_amount' => 5000.00,
            'currency' => 'EUR',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => CampaignStatus::WAITING_FOR_VALIDATION->value,
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('campaigns.update', ['id' => $this->draftCampaign->id]), $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Verify status changed
        $this->assertDatabaseHas('campaigns', [
            'id' => $this->draftCampaign->id,
            'title' => 'Updated Campaign Title',
            'status' => CampaignStatus::WAITING_FOR_VALIDATION->value,
        ]);
    }

    /** @test */
    public function user_can_update_own_waiting_for_validation_campaign(): void
    {
        $updateData = [
            'title' => 'Updated Waiting Campaign',
            'description' => 'New description',
            'status' => CampaignStatus::WAITING_FOR_VALIDATION->value,
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('campaigns.update', ['id' => $this->waitingCampaign->id]), $updateData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $this->waitingCampaign->id,
            'title' => 'Updated Waiting Campaign',
        ]);
    }

    /** @test */
    public function user_cannot_update_own_active_campaign(): void
    {
        $updateData = [
            'title' => 'Trying to update active campaign',
            'status' => CampaignStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('campaigns.update', ['id' => $this->activeCampaign->id]), $updateData);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You are not authorized to update this campaign.',
        ]);
    }

    /** @test */
    public function user_cannot_update_campaign_created_by_another_user(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');

        $otherCampaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'created_by' => $otherUser->id,
        ]);

        $updateData = [
            'title' => 'Trying to update someone else campaign',
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('campaigns.update', ['id' => $otherCampaign->id]), $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function campaign_manager_can_update_any_draft_campaign(): void
    {
        $updateData = [
            'title' => 'Manager Updated Title',
            'status' => CampaignStatus::DRAFT->value,
        ];

        $response = $this->actingAs($this->campaignManager)
            ->putJson(route('campaigns.update', ['id' => $this->draftCampaign->id]), $updateData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $this->draftCampaign->id,
            'title' => 'Manager Updated Title',
        ]);
    }

    /** @test */
    public function update_campaign_with_tags(): void
    {
        // Create some existing tags
        $existingTag = Tag::factory()->create(['name' => 'ExistingTag']);

        $updateData = [
            'title' => 'Campaign with Tags',
            'tags' => ['ExistingTag', 'NewTag1', 'NewTag2'],
            'status' => CampaignStatus::DRAFT->value,
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('campaigns.update', ['id' => $this->draftCampaign->id]), $updateData);

        $response->assertStatus(200);

        // Verify tags are associated
        $updatedCampaign = Campaign::findById($this->draftCampaign->id)->firstOrFail();
        $this->assertCount(3, $updatedCampaign->tags);
        $this->assertTrue($updatedCampaign->tags->contains('name', 'ExistingTag'));
        $this->assertTrue($updatedCampaign->tags->contains('name', 'NewTag1'));
        $this->assertTrue($updatedCampaign->tags->contains('name', 'NewTag2'));
    }

    /** @test */
    public function update_campaign_can_change_category(): void
    {
        $newCategory = Category::factory()->create(['is_active' => true]);

        $updateData = [
            'title' => 'Campaign with New Category',
            'category_id' => $newCategory->id,
            'status' => CampaignStatus::DRAFT->value,
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('campaigns.update', ['id' => $this->draftCampaign->id]), $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('campaigns', [
            'id' => $this->draftCampaign->id,
            'category_id' => $newCategory->id,
        ]);
    }

    /** @test */
    public function guest_user_cannot_update_campaign(): void
    {
        $updateData = [
            'title' => 'Unauthorized Update',
        ];

        $response = $this->putJson(route('campaigns.update', ['id' => $this->draftCampaign->id]), $updateData);

        $response->assertStatus(401);
    }

    /** @test */
    public function update_returns_404_for_nonexistent_campaign(): void
    {
        $updateData = [
            'title' => 'Update Nonexistent',
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('campaigns.update', ['id' => '00000000-0000-0000-0000-000000000000']), $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function validation_fails_when_updating_to_waiting_for_validation_without_required_fields(): void
    {
        $updateData = [
            'title' => 'Updated Title',
            'status' => CampaignStatus::WAITING_FOR_VALIDATION->value,
            // Missing required fields: goal_amount, currency, start_date, end_date
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('campaigns.update', ['id' => $this->draftCampaign->id]), $updateData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['goal_amount', 'currency', 'start_date', 'end_date']);
    }

    /** @test */
    public function campaign_manager_can_validate_waiting_for_validation_campaign(): void
    {
        $response = $this->actingAs($this->campaignManager)
            ->postJson(route('campaigns.validate', ['id' => $this->waitingCampaign->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Campaign validated successfully',
        ]);

        // Verify status changed to active
        $this->assertDatabaseHas('campaigns', [
            'id' => $this->waitingCampaign->id,
            'status' => CampaignStatus::ACTIVE->value,
        ]);
    }

    /** @test */
    public function validate_endpoint_does_not_modify_other_campaign_fields(): void
    {
        $originalTitle = $this->waitingCampaign->title;
        $originalDescription = $this->waitingCampaign->description;
        $originalGoalAmount = $this->waitingCampaign->goal_amount;

        $response = $this->actingAs($this->campaignManager)
            ->postJson(route('campaigns.validate', ['id' => $this->waitingCampaign->id]));

        $response->assertStatus(200);

        // Verify only status changed
        $updatedCampaign = Campaign::findById($this->waitingCampaign->id)->firstOrFail();
        $this->assertEquals($originalTitle, $updatedCampaign->title);
        $this->assertEquals($originalDescription, $updatedCampaign->description);
        $this->assertEquals($originalGoalAmount, $updatedCampaign->goal_amount);
        $this->assertEquals(CampaignStatus::ACTIVE, $updatedCampaign->status);
    }

    /** @test */
    public function regular_user_cannot_validate_campaign(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson(route('campaigns.validate', ['id' => $this->waitingCampaign->id]));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You are not authorized to validate campaigns.',
        ]);

        // Verify status did NOT change
        $this->assertDatabaseHas('campaigns', [
            'id' => $this->waitingCampaign->id,
            'status' => CampaignStatus::WAITING_FOR_VALIDATION->value,
        ]);
    }

    /** @test */
    public function guest_user_cannot_validate_campaign(): void
    {
        $response = $this->postJson(route('campaigns.validate', ['id' => $this->waitingCampaign->id]));

        $response->assertStatus(401);
    }

    /** @test */
    public function validate_returns_404_for_nonexistent_campaign(): void
    {
        $response = $this->actingAs($this->campaignManager)
            ->postJson(route('campaigns.validate', ['id' => '00000000-0000-0000-0000-000000000000']));

        $response->assertStatus(404);
    }

    /** @test */
    public function campaign_manager_can_validate_draft_campaign(): void
    {
        // Although unusual, the validate endpoint should work on any status
        $response = $this->actingAs($this->campaignManager)
            ->postJson(route('campaigns.validate', ['id' => $this->draftCampaign->id]));

        $response->assertStatus(200);

        // Verify status changed to active
        $this->assertDatabaseHas('campaigns', [
            'id' => $this->draftCampaign->id,
            'status' => CampaignStatus::ACTIVE->value,
        ]);
    }

    /** @test */
    public function update_campaign_partial_update_only_sends_provided_fields(): void
    {
        $originalTitle = $this->draftCampaign->title;
        $originalGoalAmount = $this->draftCampaign->goal_amount;

        // Only update description
        $updateData = [
            'description' => 'Only updating description',
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('campaigns.update', ['id' => $this->draftCampaign->id]), $updateData);

        $response->assertStatus(200);

        // Verify only description changed
        $updatedCampaign = Campaign::findById($this->draftCampaign->id)->firstOrFail();
        $this->assertEquals($originalTitle, $updatedCampaign->title);
        $this->assertEquals($originalGoalAmount, $updatedCampaign->goal_amount);
        $this->assertEquals('Only updating description', $updatedCampaign->description);
    }
}
