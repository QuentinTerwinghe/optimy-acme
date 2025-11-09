<?php

declare(strict_types=1);

namespace Tests\Feature\Campaign;

use App\Enums\CampaignStatus;
use App\Enums\Currency;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use App\Models\Campaign\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();

        // Create a test category
        $this->category = Category::factory()->create([
            'name' => 'Education',
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_create_campaign_as_draft(): void
    {
        $campaignData = [
            'title' => 'Test Campaign',
            'description' => 'This is a test campaign',
            'goal_amount' => 1000.00,
            'currency' => Currency::EUR->value,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => CampaignStatus::DRAFT->value,
            'category_id' => $this->category->id,
            'tags' => ['Education', 'Technology'],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', $campaignData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Campaign created successfully',
            ]);

        $this->assertDatabaseHas('campaigns', [
            'title' => 'Test Campaign',
            'description' => 'This is a test campaign',
            'goal_amount' => 1000.00,
            'currency' => Currency::EUR->value,
            'status' => CampaignStatus::DRAFT->value,
            'category_id' => $this->category->id,
        ]);

        // Check tags were created and attached
        $this->assertDatabaseHas('tags', ['name' => 'Education']);
        $this->assertDatabaseHas('tags', ['name' => 'Technology']);

        $campaign = Campaign::where('title', 'Test Campaign')->first();
        $this->assertNotNull($campaign);
        $this->assertCount(2, $campaign->tags);
    }

    public function test_authenticated_user_can_create_campaign_waiting_for_validation(): void
    {
        $campaignData = [
            'title' => 'Campaign Waiting Validation',
            'description' => 'This campaign should wait for validation',
            'goal_amount' => 5000.00,
            'currency' => Currency::USD->value,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(60)->format('Y-m-d'),
            'status' => CampaignStatus::WAITING_FOR_VALIDATION->value,
            'category_id' => $this->category->id,
            'tags' => [],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', $campaignData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Campaign created successfully',
            ]);

        $this->assertDatabaseHas('campaigns', [
            'title' => 'Campaign Waiting Validation',
            'status' => CampaignStatus::WAITING_FOR_VALIDATION->value,
        ]);
    }

    public function test_can_create_campaign_with_existing_tags(): void
    {
        // Create existing tags
        $tag1 = Tag::factory()->create(['name' => 'Health']);
        $tag2 = Tag::factory()->create(['name' => 'Community']);

        $campaignData = [
            'title' => 'Health Campaign',
            'description' => 'Health community campaign',
            'goal_amount' => 2000.00,
            'currency' => Currency::EUR->value,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(45)->format('Y-m-d'),
            'status' => CampaignStatus::DRAFT->value,
            'category_id' => $this->category->id,
            'tags' => ['Health', 'Community'],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', $campaignData);

        $response->assertStatus(201);

        $campaign = Campaign::where('title', 'Health Campaign')->first();
        $this->assertCount(2, $campaign->tags);

        // Should not create duplicate tags
        $this->assertEquals(2, Tag::whereIn('name', ['Health', 'Community'])->count());
    }

    public function test_can_create_campaign_with_mixed_new_and_existing_tags(): void
    {
        // Create one existing tag
        Tag::factory()->create(['name' => 'Science']);

        $campaignData = [
            'title' => 'Science & Innovation',
            'description' => 'A campaign about science and innovation',
            'goal_amount' => 3000.00,
            'currency' => Currency::GBP->value,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => CampaignStatus::DRAFT->value,
            'category_id' => $this->category->id,
            'tags' => ['Science', 'Innovation', 'Research'], // Mix of existing and new
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', $campaignData);

        $response->assertStatus(201);

        // Check new tags were created
        $this->assertDatabaseHas('tags', ['name' => 'Innovation']);
        $this->assertDatabaseHas('tags', ['name' => 'Research']);

        $campaign = Campaign::where('title', 'Science & Innovation')->first();
        $this->assertCount(3, $campaign->tags);
    }

    public function test_can_create_campaign_without_category(): void
    {
        $campaignData = [
            'title' => 'No Category Campaign',
            'description' => 'Campaign without a category',
            'goal_amount' => 1500.00,
            'currency' => Currency::EUR->value,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(20)->format('Y-m-d'),
            'status' => CampaignStatus::DRAFT->value,
            'category_id' => null,
            'tags' => [],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', $campaignData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('campaigns', [
            'title' => 'No Category Campaign',
            'category_id' => null,
        ]);
    }

    public function test_can_create_draft_campaign_with_only_title(): void
    {
        $campaignData = [
            'title' => 'Draft Campaign',
            'status' => CampaignStatus::DRAFT->value,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', $campaignData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Campaign created successfully',
            ]);

        $this->assertDatabaseHas('campaigns', [
            'title' => 'Draft Campaign',
            'status' => CampaignStatus::DRAFT->value,
            'goal_amount' => null,
            'currency' => null,
            'start_date' => null,
            'end_date' => null,
        ]);
    }

    public function test_validation_fails_without_required_fields_for_non_draft(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', [
                'title' => 'Active Campaign',
                'status' => CampaignStatus::ACTIVE->value,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['goal_amount', 'currency', 'start_date', 'end_date']);
    }

    public function test_validation_fails_without_title(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_validation_fails_with_invalid_currency(): void
    {
        $campaignData = [
            'title' => 'Invalid Currency Campaign',
            'description' => 'Test',
            'goal_amount' => 1000.00,
            'currency' => 'INVALID',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => CampaignStatus::ACTIVE->value, // Use ACTIVE status to require currency
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', $campaignData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);
    }

    public function test_validation_fails_when_end_date_before_start_date(): void
    {
        $campaignData = [
            'title' => 'Invalid Dates Campaign',
            'description' => 'Test',
            'goal_amount' => 1000.00,
            'currency' => Currency::EUR->value,
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'), // Before start date
            'status' => CampaignStatus::DRAFT->value,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', $campaignData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_unauthenticated_user_cannot_create_campaign(): void
    {
        $campaignData = [
            'title' => 'Unauthorized Campaign',
            'description' => 'This should fail',
            'goal_amount' => 1000.00,
            'currency' => Currency::EUR->value,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => CampaignStatus::DRAFT->value,
        ];

        $response = $this->postJson('/campaigns', $campaignData);

        $response->assertStatus(401);
    }

    public function test_current_amount_defaults_to_zero(): void
    {
        $campaignData = [
            'title' => 'Default Amount Campaign',
            'description' => 'Testing default current amount',
            'goal_amount' => 1000.00,
            'currency' => Currency::EUR->value,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => CampaignStatus::DRAFT->value,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/campaigns', $campaignData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('campaigns', [
            'title' => 'Default Amount Campaign',
            'current_amount' => 0,
        ]);
    }
}
