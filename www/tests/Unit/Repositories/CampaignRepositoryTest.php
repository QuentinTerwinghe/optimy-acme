<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Enums\Campaign\CampaignStatus;
use App\Models\Campaign\Campaign;
use App\Repositories\Campaign\CampaignRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Campaign Repository Unit Tests
 *
 * Tests the repository pattern implementation for campaigns
 */
class CampaignRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CampaignRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CampaignRepository();
    }

    #[Test]
    public function it_can_create_a_campaign(): void
    {
        $data = [
            'title' => 'Test Campaign',
            'description' => 'Test Description',
            'status' => CampaignStatus::DRAFT,
        ];

        $campaign = $this->repository->create($data);

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals('Test Campaign', $campaign->title);
        $this->assertDatabaseHas('campaigns', ['title' => 'Test Campaign']);
    }

    #[Test]
    public function it_can_find_a_campaign_by_id(): void
    {
        $campaign = Campaign::factory()->create(['title' => 'Find Me']);

        $found = $this->repository->find($campaign->id);

        $this->assertInstanceOf(Campaign::class, $found);
        $this->assertEquals('Find Me', $found->title);
    }

    #[Test]
    public function it_returns_null_when_campaign_not_found(): void
    {
        $found = $this->repository->find('non-existent-id');

        $this->assertNull($found);
    }

    #[Test]
    public function it_can_find_campaign_with_relations(): void
    {
        $campaign = Campaign::factory()->create();

        $found = $this->repository->findWithRelations($campaign->id, ['category']);

        $this->assertInstanceOf(Campaign::class, $found);
        $this->assertTrue($found->relationLoaded('category'));
    }

    #[Test]
    public function it_can_update_a_campaign(): void
    {
        $campaign = Campaign::factory()->create(['title' => 'Original Title']);

        $updated = $this->repository->update($campaign, ['title' => 'Updated Title']);

        $this->assertTrue($updated);
        $this->assertEquals('Updated Title', $campaign->fresh()->title);
    }

    #[Test]
    public function it_can_delete_a_campaign(): void
    {
        $campaign = Campaign::factory()->create();
        $campaignId = $campaign->id;

        $deleted = $this->repository->delete($campaign);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('campaigns', ['id' => $campaignId]);
    }

    #[Test]
    public function it_can_get_all_campaigns(): void
    {
        Campaign::factory()->count(3)->create();

        $campaigns = $this->repository->getAll();

        $this->assertCount(3, $campaigns);
    }

    #[Test]
    public function it_can_get_campaigns_by_status(): void
    {
        Campaign::factory()->count(2)->create(['status' => CampaignStatus::ACTIVE]);
        Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

        $activeCampaigns = $this->repository->getByStatus(CampaignStatus::ACTIVE);

        $this->assertCount(2, $activeCampaigns);
        $this->assertTrue($activeCampaigns->every(fn ($c) => $c->status === CampaignStatus::ACTIVE));
    }

    #[Test]
    public function it_can_count_campaigns_by_status(): void
    {
        Campaign::factory()->count(3)->create(['status' => CampaignStatus::COMPLETED]);
        Campaign::factory()->create(['status' => CampaignStatus::ACTIVE]);

        $count = $this->repository->countByStatus(CampaignStatus::COMPLETED);

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function it_can_count_active_campaigns(): void
    {
        // Active campaign (within date range)
        Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        // Not active (wrong status)
        Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        // Not active (expired)
        Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDay(),
        ]);

        $count = $this->repository->countActiveCampaigns();

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function it_can_sum_field_by_status(): void
    {
        Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'current_amount' => 100.0,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'current_amount' => 200.0,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'current_amount' => 50.0,
        ]);

        $sum = $this->repository->sumByStatus(
            'current_amount',
            [CampaignStatus::ACTIVE, CampaignStatus::COMPLETED]
        );

        $this->assertEquals(300.0, $sum);
    }

    #[Test]
    public function it_can_get_aggregated_funding_data(): void
    {
        Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'goal_amount' => 1000.0,
            'current_amount' => 500.0,
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::COMPLETED,
            'goal_amount' => 2000.0,
            'current_amount' => 2000.0,
        ]);

        $data = $this->repository->getAggregatedFundingData([
            CampaignStatus::ACTIVE,
            CampaignStatus::COMPLETED,
        ]);

        $this->assertEquals(3000.0, $data['total_goal']);
        $this->assertEquals(2500.0, $data['total_raised']);
    }
}
