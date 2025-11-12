<?php

declare(strict_types=1);

namespace Tests\Feature\Donation;

use App\Enums\Campaign\CampaignStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function it_shows_donation_page_for_active_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'title' => 'Test Campaign',
            'description' => 'Test Description',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('donations.create', $campaign->id));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('donations.create');
        $response->assertViewHas('campaign');
        $response->assertViewHas('quickAmounts', [5, 10, 20, 50, 100]);
    }

    /**
     * @test
     */
    public function it_redirects_with_error_when_campaign_is_not_active(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('donations.create', $campaign->id));

        // Assert
        $response->assertRedirect(route('campaigns.show', $campaign->id));
        $response->assertSessionHas('error', 'This campaign is not accepting donations at this time.');
    }

    /**
     * @test
     */
    public function it_redirects_when_campaign_is_waiting_for_validation(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('donations.create', $campaign->id));

        // Assert
        $response->assertRedirect(route('campaigns.show', $campaign->id));
        $response->assertSessionHas('error');
    }

    /**
     * @test
     */
    public function it_redirects_when_campaign_is_rejected(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::REJECTED,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('donations.create', $campaign->id));

        // Assert
        $response->assertRedirect(route('campaigns.show', $campaign->id));
        $response->assertSessionHas('error');
    }

    /**
     * @test
     */
    public function it_redirects_when_campaign_is_completed(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('donations.create', $campaign->id));

        // Assert
        $response->assertRedirect(route('campaigns.show', $campaign->id));
        $response->assertSessionHas('error');
    }

    /**
     * @test
     */
    public function it_redirects_when_campaign_is_cancelled(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::CANCELLED,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('donations.create', $campaign->id));

        // Assert
        $response->assertRedirect(route('campaigns.show', $campaign->id));
        $response->assertSessionHas('error');
    }

    /**
     * @test
     */
    public function it_redirects_to_dashboard_when_campaign_not_found(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->get(route('donations.create', 'non-existent-id'));

        // Assert
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Campaign not found.');
    }

    /**
     * @test
     */
    public function it_requires_authentication_to_access_donation_page(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
        ]);

        // Act
        $response = $this->get(route('donations.create', $campaign->id));

        // Assert
        $response->assertRedirect(route('login.form'));
    }
}
