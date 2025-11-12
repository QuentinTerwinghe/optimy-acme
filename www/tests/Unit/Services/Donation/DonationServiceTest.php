<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Donation;

use App\Enums\Common\Currency;
use App\Models\Campaign\Campaign;
use App\Services\Donation\DonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DonationServiceTest extends TestCase
{
    use RefreshDatabase;

    private DonationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DonationService();
    }

    #[Test]
    public function it_returns_donation_page_data_for_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'description' => 'Test Description',
            'currency' => Currency::USD,
            'goal_amount' => '1000.00',
            'current_amount' => '250.00',
        ]);

        // Act
        $result = $this->service->getDonationPageData($campaign);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('campaign', $result);

        $campaignData = $result['campaign'];
        $this->assertEquals($campaign->id, $campaignData['id']);
        $this->assertEquals('Test Campaign', $campaignData['title']);
        $this->assertEquals('Test Description', $campaignData['description']);
        $this->assertEquals('USD', $campaignData['currency']);
        $this->assertEquals('1000.00', $campaignData['goal_amount']);
        $this->assertEquals('250.00', $campaignData['current_amount']);
    }

    #[Test]
    public function it_returns_standard_quick_donation_amounts(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'currency' => Currency::USD,
        ]);

        // Act
        $result = $this->service->getQuickDonationAmounts($campaign);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals([5, 10, 20, 50, 100], $result);
    }

    #[Test]
    public function it_returns_quick_amounts_for_different_currencies(): void
    {
        // Arrange - EUR
        $campaignEur = Campaign::factory()->create([
            'currency' => Currency::EUR,
        ]);

        // Arrange - GBP
        $campaignGbp = Campaign::factory()->create([
            'currency' => Currency::GBP,
        ]);

        // Act
        $resultEur = $this->service->getQuickDonationAmounts($campaignEur);
        $resultGbp = $this->service->getQuickDonationAmounts($campaignGbp);

        // Assert - All currencies use same standard amounts
        $this->assertEquals([5, 10, 20, 50, 100], $resultEur);
        $this->assertEquals([5, 10, 20, 50, 100], $resultGbp);
    }
}
