<?php

declare(strict_types=1);

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\CampaignStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create a test category
        $this->category = Category::factory()->create([
            'name' => 'Test Category',
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_get_total_funds_raised(): void
    {
        // Create campaigns with different statuses
        Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'current_amount' => 1000.00,
            'category_id' => $this->category->id,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'current_amount' => 2500.50,
            'category_id' => $this->category->id,
        ]);

        // This should NOT be included (draft status)
        Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'current_amount' => 500.00,
            'category_id' => $this->category->id,
        ]);

        // This should NOT be included (cancelled status)
        Campaign::factory()->create([
            'status' => CampaignStatus::CANCELLED,
            'current_amount' => 300.00,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/total-funds-raised');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 3500.50,
        ]);
    }

    public function test_total_funds_raised_returns_zero_when_no_campaigns(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/total-funds-raised');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0.0,
        ]);
    }

    public function test_authenticated_user_can_get_completed_campaigns_count(): void
    {
        // Create campaigns with different statuses
        Campaign::factory()->count(3)->create([
            'status' => CampaignStatus::COMPLETED,
            'category_id' => $this->category->id,
        ]);

        Campaign::factory()->count(2)->create([
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/completed-count');

        $response->assertStatus(200);
        $response->assertJson([
            'count' => 3,
        ]);
    }

    public function test_completed_campaigns_count_returns_zero_when_no_completed_campaigns(): void
    {
        // Create only non-completed campaigns
        Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/completed-count');

        $response->assertStatus(200);
        $response->assertJson([
            'count' => 0,
        ]);
    }

    public function test_guest_cannot_access_total_funds_raised_endpoint(): void
    {
        $response = $this->getJson('/api/campaigns/stats/total-funds-raised');

        $response->assertStatus(401);
    }

    public function test_guest_cannot_access_completed_campaigns_count_endpoint(): void
    {
        $response = $this->getJson('/api/campaigns/stats/completed-count');

        $response->assertStatus(401);
    }

    public function test_total_funds_raised_handles_decimal_precision(): void
    {
        Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'current_amount' => 1234.56,
            'category_id' => $this->category->id,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'current_amount' => 789.44,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/total-funds-raised');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 2024.00,
        ]);
    }

    public function test_completed_campaigns_count_ignores_waiting_for_validation_status(): void
    {
        Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'category_id' => $this->category->id,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/completed-count');

        $response->assertStatus(200);
        $response->assertJson([
            'count' => 1,
        ]);
    }

    public function test_authenticated_user_can_get_fundraising_progress(): void
    {
        // Create campaigns with different statuses
        Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'goal_amount' => 10000.00,
            'current_amount' => 3000.00,
            'category_id' => $this->category->id,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'goal_amount' => 5000.00,
            'current_amount' => 5500.00,
            'category_id' => $this->category->id,
        ]);

        // This should NOT be included (draft status)
        Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'goal_amount' => 2000.00,
            'current_amount' => 500.00,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/fundraising-progress');

        $response->assertStatus(200);
        $response->assertJson([
            'total_goal' => 15000.00,
            'total_raised' => 8500.00,
            'percentage' => 56.67,
        ]);
    }

    public function test_fundraising_progress_returns_zeros_when_no_campaigns(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/fundraising-progress');

        $response->assertStatus(200);
        $response->assertJson([
            'total_goal' => 0.0,
            'total_raised' => 0.0,
            'percentage' => 0.0,
        ]);
    }

    public function test_fundraising_progress_calculates_percentage_correctly(): void
    {
        Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'goal_amount' => 1000.00,
            'current_amount' => 250.00,
            'category_id' => $this->category->id,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'goal_amount' => 2000.00,
            'current_amount' => 1750.00,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/fundraising-progress');

        $response->assertStatus(200);
        $response->assertJson([
            'total_goal' => 3000.00,
            'total_raised' => 2000.00,
            'percentage' => 66.67,
        ]);
    }

    public function test_fundraising_progress_handles_over_100_percent(): void
    {
        Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'goal_amount' => 1000.00,
            'current_amount' => 1500.00,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/fundraising-progress');

        $response->assertStatus(200);
        $response->assertJson([
            'total_goal' => 1000.00,
            'total_raised' => 1500.00,
            'percentage' => 150.00,
        ]);
    }

    public function test_guest_cannot_access_fundraising_progress_endpoint(): void
    {
        $response = $this->getJson('/api/campaigns/stats/fundraising-progress');

        $response->assertStatus(401);
    }

    public function test_fundraising_progress_ignores_cancelled_campaigns(): void
    {
        Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'goal_amount' => 1000.00,
            'current_amount' => 500.00,
            'category_id' => $this->category->id,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::CANCELLED,
            'goal_amount' => 2000.00,
            'current_amount' => 100.00,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/campaigns/stats/fundraising-progress');

        $response->assertStatus(200);
        $response->assertJson([
            'total_goal' => 1000.00,
            'total_raised' => 500.00,
            'percentage' => 50.00,
        ]);
    }
}
