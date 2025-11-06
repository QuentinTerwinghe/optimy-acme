<?php

declare(strict_types=1);

use App\Contracts\Services\CampaignServiceInterface;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CampaignService', function () {
    beforeEach(function () {
        $this->service = app(CampaignServiceInterface::class);
    });

    test('is bound to interface', function () {
        expect($this->service)->toBeInstanceOf(CampaignService::class);
    });

    test('getActiveCampaigns returns only active campaigns', function () {
        // Create campaigns with different statuses
        Campaign::factory()->draft()->create();
        Campaign::factory()->completed()->create();
        Campaign::factory()->cancelled()->create();
        $activeCampaign = Campaign::factory()->active()->create();

        $result = $this->service->getActiveCampaigns();

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($activeCampaign->id)
            ->and($result->first()->status)->toBe(CampaignStatus::ACTIVE);
    });

    test('getActiveCampaigns returns only campaigns with future end dates', function () {
        // Create active campaign that has already ended
        Campaign::factory()->active()->create([
            'end_date' => now()->subDay(),
        ]);

        // Create active campaign that ends in the future
        $futureCampaign = Campaign::factory()->active()->create([
            'end_date' => now()->addWeek(),
        ]);

        $result = $this->service->getActiveCampaigns();

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($futureCampaign->id);
    });

    test('getActiveCampaigns orders by end_date ascending', function () {
        // Create campaigns ending at different times
        $campaign3 = Campaign::factory()->active()->create([
            'end_date' => now()->addWeeks(3),
        ]);
        $campaign1 = Campaign::factory()->active()->create([
            'end_date' => now()->addWeek(),
        ]);
        $campaign2 = Campaign::factory()->active()->create([
            'end_date' => now()->addWeeks(2),
        ]);

        $result = $this->service->getActiveCampaigns();

        expect($result)->toHaveCount(3)
            ->and($result->first()->id)->toBe($campaign1->id)
            ->and($result->get(1)->id)->toBe($campaign2->id)
            ->and($result->last()->id)->toBe($campaign3->id);
    });

    test('getActiveCampaigns returns empty collection when no active campaigns', function () {
        Campaign::factory()->draft()->create();
        Campaign::factory()->completed()->create();

        $result = $this->service->getActiveCampaigns();

        expect($result)->toBeEmpty();
    });

    test('getActiveCampaigns returns multiple active campaigns', function () {
        $count = 5;
        Campaign::factory()->active()->count($count)->create();

        $result = $this->service->getActiveCampaigns();

        expect($result)->toHaveCount($count)
            ->and($result->every(fn ($campaign) => $campaign->status === CampaignStatus::ACTIVE))->toBeTrue();
    });

    test('getActiveCampaigns excludes campaigns ending today in the past', function () {
        // Campaign that ended earlier today
        Campaign::factory()->active()->create([
            'end_date' => now()->startOfDay(),
        ]);

        // Campaign ending later today
        $laterToday = Campaign::factory()->active()->create([
            'end_date' => now()->endOfDay(),
        ]);

        $result = $this->service->getActiveCampaigns();

        // Should only include the one ending later today (future)
        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($laterToday->id);
    });

    test('getActiveCampaigns handles database errors gracefully', function () {
        // Close the database connection to simulate an error
        DB::disconnect();

        $result = $this->service->getActiveCampaigns();

        expect($result)->toBeEmpty();

        // Reconnect for other tests
        DB::reconnect();
    });

    test('getActiveCampaigns returns collection', function () {
        $result = $this->service->getActiveCampaigns();

        expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });
});
