<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Donation;

use App\Enums\Campaign\CampaignStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Policies\Donation\DonationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationPolicyTest extends TestCase
{
    use RefreshDatabase;

    private DonationPolicy $policy;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new DonationPolicy();
        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function it_allows_donation_when_campaign_is_active(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
        ]);

        // Act
        $result = $this->policy->donate($this->user, $campaign);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_denies_donation_when_campaign_is_draft(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
        ]);

        // Act
        $result = $this->policy->donate($this->user, $campaign);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_denies_donation_when_campaign_is_waiting_for_validation(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
        ]);

        // Act
        $result = $this->policy->donate($this->user, $campaign);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_denies_donation_when_campaign_is_rejected(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::REJECTED,
        ]);

        // Act
        $result = $this->policy->donate($this->user, $campaign);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_denies_donation_when_campaign_is_completed(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
        ]);

        // Act
        $result = $this->policy->donate($this->user, $campaign);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_denies_donation_when_campaign_is_cancelled(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::CANCELLED,
        ]);

        // Act
        $result = $this->policy->donate($this->user, $campaign);

        // Assert
        $this->assertFalse($result);
    }
}
