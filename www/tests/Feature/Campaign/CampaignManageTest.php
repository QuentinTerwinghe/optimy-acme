<?php

declare(strict_types=1);

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\CampaignPermissions;
use App\Enums\Campaign\CampaignStatus;
use App\Enums\Common\Currency;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CampaignManageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $campaignManager;
    private User $admin;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $manageAllCampaignsPermission = Permission::firstOrCreate([
            'name' => CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value
        ]);

        // Create roles
        $campaignManagerRole = Role::create(['name' => 'campaign_manager']);
        $adminRole = Role::create(['name' => 'admin']);

        // Assign permissions to roles
        $campaignManagerRole->givePermissionTo($manageAllCampaignsPermission);
        $adminRole->givePermissionTo($manageAllCampaignsPermission);

        // Create test users
        $this->user = User::factory()->create();
        $this->campaignManager = User::factory()->create();
        $this->campaignManager->assignRole('campaign_manager');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create a test category
        $this->category = Category::factory()->create([
            'name' => 'Education',
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_access_campaign_management_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('campaigns.index'));

        $response->assertStatus(200);
        $response->assertViewIs('campaigns.manage');
    }

    public function test_guest_cannot_access_campaign_management_page(): void
    {
        $response = $this->get(route('campaigns.index'));

        $response->assertRedirect(route('login.form'));
    }

    public function test_regular_user_can_only_see_their_own_campaigns(): void
    {
        // Create campaigns by the user
        $userCampaigns = Campaign::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'status' => CampaignStatus::DRAFT,
            'category_id' => $this->category->id,
        ]);

        // Create campaigns by another user
        $otherUser = User::factory()->create();
        $otherCampaigns = Campaign::factory()->count(2)->create([
            'created_by' => $otherUser->id,
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/manage');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        // Verify only user's campaigns are returned
        $returnedIds = collect($response->json('data'))->pluck('id')->toArray();
        foreach ($userCampaigns as $campaign) {
            $this->assertContains($campaign->id, $returnedIds);
        }
        foreach ($otherCampaigns as $campaign) {
            $this->assertNotContains($campaign->id, $returnedIds);
        }
    }

    public function test_campaign_manager_can_see_all_campaigns(): void
    {
        // Create campaigns by different users
        $userCampaigns = Campaign::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'status' => CampaignStatus::DRAFT,
            'category_id' => $this->category->id,
        ]);

        $otherUser = User::factory()->create();
        $otherCampaigns = Campaign::factory()->count(2)->create([
            'created_by' => $otherUser->id,
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->campaignManager)
            ->getJson('/api/campaigns/manage');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data'); // All campaigns

        // Verify all campaigns are returned
        $returnedIds = collect($response->json('data'))->pluck('id')->toArray();
        foreach ($userCampaigns as $campaign) {
            $this->assertContains($campaign->id, $returnedIds);
        }
        foreach ($otherCampaigns as $campaign) {
            $this->assertContains($campaign->id, $returnedIds);
        }
    }

    public function test_admin_can_see_all_campaigns(): void
    {
        // Create campaigns by different users
        $userCampaigns = Campaign::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'status' => CampaignStatus::DRAFT,
            'category_id' => $this->category->id,
        ]);

        $otherUser = User::factory()->create();
        $otherCampaigns = Campaign::factory()->count(2)->create([
            'created_by' => $otherUser->id,
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/campaigns/manage');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data'); // All campaigns

        // Verify all campaigns are returned
        $returnedIds = collect($response->json('data'))->pluck('id')->toArray();
        foreach ($userCampaigns as $campaign) {
            $this->assertContains($campaign->id, $returnedIds);
        }
        foreach ($otherCampaigns as $campaign) {
            $this->assertContains($campaign->id, $returnedIds);
        }
    }

    public function test_campaigns_api_returns_correct_structure(): void
    {
        $campaign = Campaign::factory()->create([
            'created_by' => $this->user->id,
            'title' => 'Test Campaign',
            'description' => 'Test Description',
            'goal_amount' => 1000.00,
            'current_amount' => 250.00,
            'currency' => Currency::USD,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(30),
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/manage');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'goal_amount',
                    'current_amount',
                    'currency',
                    'start_date',
                    'start_date_formatted',
                    'end_date',
                    'end_date_formatted',
                    'status',
                    'status_label',
                    'progress_percentage',
                    'days_remaining',
                    'category',
                    'tags',
                ],
            ],
        ]);

        // Verify specific data
        $campaignData = $response->json('data.0');
        $this->assertEquals('Test Campaign', $campaignData['title']);
        $this->assertEquals('Test Description', $campaignData['description']);
        $this->assertEquals('1000.00', $campaignData['goal_amount']);
        $this->assertEquals('250.00', $campaignData['current_amount']);
        $this->assertEquals('USD', $campaignData['currency']);
        $this->assertEquals('active', $campaignData['status']);
        $this->assertEquals('Active', $campaignData['status_label']);
    }

    public function test_guest_cannot_access_campaigns_api(): void
    {
        $response = $this->getJson('/api/campaigns/manage');

        $response->assertStatus(401);
    }

    public function test_campaigns_include_category_and_tags_when_loaded(): void
    {
        $campaign = Campaign::factory()->create([
            'created_by' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => CampaignStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/manage');

        $response->assertStatus(200);

        $campaignData = $response->json('data.0');
        $this->assertArrayHasKey('category', $campaignData);
        $this->assertArrayHasKey('tags', $campaignData);
    }

    public function test_campaigns_ordered_by_creation_date_desc(): void
    {
        // Create campaigns with different creation times
        $oldCampaign = Campaign::factory()->create([
            'created_by' => $this->user->id,
            'title' => 'Old Campaign',
            'category_id' => $this->category->id,
            'creation_date' => now()->subDays(5),
        ]);

        $newCampaign = Campaign::factory()->create([
            'created_by' => $this->user->id,
            'title' => 'New Campaign',
            'category_id' => $this->category->id,
            'creation_date' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/manage');

        $response->assertStatus(200);

        $campaigns = $response->json('data');
        $this->assertEquals('New Campaign', $campaigns[0]['title']);
        $this->assertEquals('Old Campaign', $campaigns[1]['title']);
    }
}
