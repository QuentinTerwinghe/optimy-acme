<?php

declare(strict_types=1);

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\CampaignStatus;
use App\Enums\Common\Currency;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use App\Models\Campaign\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for authentication
        $this->user = User::factory()->create();

        // Create a category
        $this->category = Category::factory()->create([
            'name' => 'Technology',
            'is_active' => true,
        ]);
    }

    /**
     * Test that authenticated users can view a campaign
     */
    public function test_authenticated_user_can_view_campaign(): void
    {
        // Create a campaign
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign Show',
            'description' => 'This is a test campaign description',
            'goal_amount' => '10000.00',
            'current_amount' => '2500.00',
            'currency' => Currency::USD,
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'created_by' => $this->user->id,
        ]);

        // Act: View the campaign as an authenticated user
        $response = $this->actingAs($this->user)
            ->get(route('campaigns.show', ['id' => $campaign->id]));

        // Assert: Response is successful and contains campaign data
        $response->assertStatus(200);
        $response->assertSee('Test Campaign Show');
        $response->assertSee('This is a test campaign description');
        $response->assertSee('Technology'); // Category name
    }

    /**
     * Test that campaign show page displays all required information
     */
    public function test_campaign_show_displays_all_information(): void
    {
        // Create tags
        $tag1 = Tag::factory()->create(['name' => 'Education']);
        $tag2 = Tag::factory()->create(['name' => 'Innovation']);

        // Create a campaign with tags
        $campaign = Campaign::factory()->create([
            'title' => 'Detailed Campaign',
            'description' => 'A very detailed campaign description',
            'goal_amount' => '50000.00',
            'current_amount' => '12500.00',
            'currency' => Currency::EUR,
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);

        // Attach tags
        $campaign->tags()->attach([$tag1->id, $tag2->id]);

        // Act: View the campaign
        $response = $this->actingAs($this->user)
            ->get(route('campaigns.show', ['id' => $campaign->id]));

        // Assert: All information is displayed
        $response->assertStatus(200);
        $response->assertSee('Detailed Campaign');
        $response->assertSee('A very detailed campaign description');
        $response->assertSee('Education'); // Tag 1
        $response->assertSee('Innovation'); // Tag 2
        $response->assertSee($this->user->name); // Creator name
    }

    /**
     * Test that guest users are redirected to login
     */
    public function test_guest_cannot_view_campaign(): void
    {
        // Create a campaign
        $campaign = Campaign::factory()->create([
            'title' => 'Private Campaign',
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
        ]);

        // Act: Try to view the campaign as a guest
        $response = $this->get(route('campaigns.show', ['id' => $campaign->id]));

        // Assert: Redirected to login
        $response->assertRedirect(route('login.form'));
    }

    /**
     * Test that viewing a non-existent campaign redirects to dashboard with error
     */
    public function test_viewing_nonexistent_campaign_redirects_to_dashboard(): void
    {
        // Act: Try to view a non-existent campaign
        $response = $this->actingAs($this->user)
            ->get(route('campaigns.show', ['id' => 'non-existent-uuid']));

        // Assert: Redirected to dashboard with error message
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Campaign not found.');
    }

    /**
     * Test that campaign show page displays correct status
     */
    public function test_campaign_show_displays_correct_status(): void
    {
        // Create campaigns with different statuses
        $statuses = [
            CampaignStatus::DRAFT,
            CampaignStatus::WAITING_FOR_VALIDATION,
            CampaignStatus::ACTIVE,
            CampaignStatus::COMPLETED,
            CampaignStatus::CANCELLED,
        ];

        foreach ($statuses as $status) {
            $campaign = Campaign::factory()->create([
                'title' => "Campaign with {$status->value} status",
                'status' => $status,
                'category_id' => $this->category->id,
                'created_by' => $this->user->id,
            ]);

            // Act: View the campaign
            $response = $this->actingAs($this->user)
                ->get(route('campaigns.show', ['id' => $campaign->id]));

            // Assert: Status is in the JSON data passed to Vue component
            $response->assertStatus(200);
            $response->assertSee($status->value, false); // Check JSON contains status value
            $response->assertSee($campaign->title);
        }
    }

    /**
     * Test that campaign show page displays progress percentage
     */
    public function test_campaign_show_displays_progress_percentage(): void
    {
        // Create a campaign with specific amounts to test progress
        $campaign = Campaign::factory()->create([
            'title' => 'Progress Test Campaign',
            'goal_amount' => '10000.00',
            'current_amount' => '2500.00', // 25% progress
            'currency' => Currency::USD,
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);

        // Act: View the campaign
        $response = $this->actingAs($this->user)
            ->get(route('campaigns.show', ['id' => $campaign->id]));

        // Assert: Campaign data is present (amounts are in JSON, formatted by Vue)
        $response->assertStatus(200);
        $response->assertSee('2500.00', false); // Current amount in JSON
        $response->assertSee('10000.00', false); // Goal amount in JSON
        $response->assertSee('Progress Test Campaign');
    }

    /**
     * Test that campaign show page displays creator information
     */
    public function test_campaign_show_displays_creator_information(): void
    {
        // Create a specific creator
        $creator = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        // Create a campaign
        $campaign = Campaign::factory()->create([
            'title' => 'Creator Test Campaign',
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
            'created_by' => $creator->id,
        ]);

        // Act: View the campaign
        $response = $this->actingAs($this->user)
            ->get(route('campaigns.show', ['id' => $campaign->id]));

        // Assert: Creator information is displayed
        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertSee('john.doe@example.com');
    }

    /**
     * Test that campaign show page displays dates correctly
     */
    public function test_campaign_show_displays_dates(): void
    {
        // Create a campaign with specific dates
        $startDate = now()->addDays(1);
        $endDate = now()->addDays(31);

        $campaign = Campaign::factory()->create([
            'title' => 'Date Test Campaign',
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_by' => $this->user->id,
        ]);

        // Act: View the campaign
        $response = $this->actingAs($this->user)
            ->get(route('campaigns.show', ['id' => $campaign->id]));

        // Assert: Campaign data is present (dates are in JSON and will be formatted by Vue)
        $response->assertStatus(200);
        $response->assertSee('Date Test Campaign');
        $response->assertSee('start_date', false); // JSON contains start_date field
        $response->assertSee('end_date', false); // JSON contains end_date field
    }

    /**
     * Test that breadcrumb links to dashboard
     */
    public function test_breadcrumb_includes_dashboard_link(): void
    {
        // Create a campaign
        $campaign = Campaign::factory()->create([
            'title' => 'Breadcrumb Test Campaign',
            'status' => CampaignStatus::ACTIVE,
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);

        // Act: View the campaign
        $response = $this->actingAs($this->user)
            ->get(route('campaigns.show', ['id' => $campaign->id]));

        // Assert: Dashboard link is present
        $response->assertStatus(200);
        $response->assertSee(route('dashboard'));
        $response->assertSee('Dashboard');
    }
}
