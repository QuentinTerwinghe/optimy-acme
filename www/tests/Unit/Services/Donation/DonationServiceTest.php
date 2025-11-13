<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Donation;

use App\Contracts\Donation\DonationRepositoryInterface;
use App\Enums\Common\Currency;
use App\Enums\Donation\DonationStatus;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use App\Services\Donation\DonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DonationServiceTest extends TestCase
{
    use RefreshDatabase;

    private DonationService $service;
    private DonationRepositoryInterface $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the repository
        $this->mockRepository = Mockery::mock(DonationRepositoryInterface::class);

        // Create service with mocked repository
        $this->service = new DonationService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

    #[Test]
    public function it_marks_donation_as_successful(): void
    {
        // Arrange
        $donation = Donation::factory()->make([
            'status' => DonationStatus::PENDING,
        ]);
        $payment = Payment::factory()->make();

        $updatedDonation = clone $donation;
        $updatedDonation->status = DonationStatus::SUCCESS;

        $this->mockRepository
            ->shouldReceive('markAsSuccessful')
            ->once()
            ->with($donation)
            ->andReturn($updatedDonation);

        // Act
        $result = $this->service->markDonationAsSuccessful($donation, $payment);

        // Assert
        $this->assertEquals(DonationStatus::SUCCESS, $result->status);
    }

    #[Test]
    public function it_does_not_update_already_successful_donation(): void
    {
        // Arrange
        $donation = Donation::factory()->make([
            'status' => DonationStatus::SUCCESS,
        ]);
        $payment = Payment::factory()->make();

        $this->mockRepository->shouldNotReceive('markAsSuccessful');

        // Act
        $result = $this->service->markDonationAsSuccessful($donation, $payment);

        // Assert
        $this->assertEquals(DonationStatus::SUCCESS, $result->status);
    }

    #[Test]
    public function it_marks_donation_as_failed(): void
    {
        // Arrange
        $donation = Donation::factory()->make([
            'status' => DonationStatus::PENDING,
        ]);
        $payment = Payment::factory()->make();
        $errorMessage = 'Payment failed';

        $updatedDonation = clone $donation;
        $updatedDonation->status = DonationStatus::FAILED;
        $updatedDonation->error_message = $errorMessage;

        $this->mockRepository
            ->shouldReceive('markAsFailed')
            ->once()
            ->with($donation, $errorMessage)
            ->andReturn($updatedDonation);

        // Act
        $result = $this->service->markDonationAsFailed($donation, $payment, $errorMessage);

        // Assert
        $this->assertEquals(DonationStatus::FAILED, $result->status);
        $this->assertEquals($errorMessage, $result->error_message);
    }

    #[Test]
    public function it_can_process_pending_donation(): void
    {
        // Arrange
        $donation = Donation::factory()->make([
            'status' => DonationStatus::PENDING,
        ]);

        // Act
        $result = $this->service->canProcessDonation($donation);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_can_process_failed_donation(): void
    {
        // Arrange
        $donation = Donation::factory()->make([
            'status' => DonationStatus::FAILED,
        ]);

        // Act
        $result = $this->service->canProcessDonation($donation);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_cannot_process_successful_donation(): void
    {
        // Arrange
        $donation = Donation::factory()->make([
            'status' => DonationStatus::SUCCESS,
        ]);

        // Act
        $result = $this->service->canProcessDonation($donation);

        // Assert
        $this->assertFalse($result);
    }
}
