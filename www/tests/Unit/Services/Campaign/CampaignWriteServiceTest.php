<?php

declare(strict_types=1);

use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Enums\Donation\DonationStatus;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

describe('CampaignWriteService::recalculateTotalAmount', function () {
    beforeEach(function () {
        $this->service = app(CampaignWriteServiceInterface::class);
    });

    test('recalculates campaign amount based on successful donations', function () {
        // Create a campaign with initial amount
        $campaign = Campaign::factory()->create([
            'current_amount' => 0,
        ]);

        // Create successful donations
        Donation::factory()->count(3)->create([
            'campaign_id' => $campaign->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Recalculate
        $result = $this->service->recalculateTotalAmount($campaign);

        expect($result)->toBeTrue()
            ->and($campaign->fresh()->current_amount)->toBe('150.00');
    });

    test('excludes pending donations from calculation', function () {
        $campaign = Campaign::factory()->create(['current_amount' => 0]);

        // Create successful and pending donations
        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 100.00,
            'status' => DonationStatus::PENDING,
        ]);

        $result = $this->service->recalculateTotalAmount($campaign);

        expect($result)->toBeTrue()
            ->and($campaign->fresh()->current_amount)->toBe('50.00');
    });

    test('excludes failed donations from calculation', function () {
        $campaign = Campaign::factory()->create(['current_amount' => 0]);

        // Create successful and failed donations
        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 100.00,
            'status' => DonationStatus::FAILED,
        ]);

        $result = $this->service->recalculateTotalAmount($campaign);

        expect($result)->toBeTrue()
            ->and($campaign->fresh()->current_amount)->toBe('50.00');
    });

    test('sets amount to zero when no successful donations', function () {
        $campaign = Campaign::factory()->create(['current_amount' => 500.00]);

        // Create only pending/failed donations
        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 50.00,
            'status' => DonationStatus::PENDING,
        ]);

        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 100.00,
            'status' => DonationStatus::FAILED,
        ]);

        $result = $this->service->recalculateTotalAmount($campaign);

        expect($result)->toBeTrue()
            ->and($campaign->fresh()->current_amount)->toBe('0.00');
    });

    test('handles campaign with no donations', function () {
        $campaign = Campaign::factory()->create(['current_amount' => 100.00]);

        $result = $this->service->recalculateTotalAmount($campaign);

        expect($result)->toBeTrue()
            ->and($campaign->fresh()->current_amount)->toBe('0.00');
    });

    test('correctly sums donations with decimal amounts', function () {
        $campaign = Campaign::factory()->create(['current_amount' => 0]);

        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 25.50,
            'status' => DonationStatus::SUCCESS,
        ]);

        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 33.75,
            'status' => DonationStatus::SUCCESS,
        ]);

        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 10.25,
            'status' => DonationStatus::SUCCESS,
        ]);

        $result = $this->service->recalculateTotalAmount($campaign);

        expect($result)->toBeTrue()
            ->and($campaign->fresh()->current_amount)->toBe('69.50');
    });

    test('logs success when recalculation completes', function () {
        $campaign = Campaign::factory()->create(['current_amount' => 0]);

        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Mock the Log facade to check that the info log is called
        Log::spy();

        $result = $this->service->recalculateTotalAmount($campaign);

        expect($result)->toBeTrue();

        // Should have received the specific log message about recalculation
        // Note: Other log calls may happen (e.g., from observers), so we just check it was called
        Log::shouldHaveReceived('info')
            ->with('Campaign amount recalculated', \Mockery::on(function ($context) use ($campaign) {
                return isset($context['campaign_id'])
                    && $context['campaign_id'] === $campaign->id
                    && isset($context['new_amount'])
                    && isset($context['successful_donations_count']);
            }));
    });

    test('only affects the specified campaign', function () {
        $campaign1 = Campaign::factory()->create(['current_amount' => 0]);
        $campaign2 = Campaign::factory()->create(['current_amount' => 0]);

        // Create donations for both campaigns
        Donation::factory()->create([
            'campaign_id' => $campaign1->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        Donation::factory()->create([
            'campaign_id' => $campaign2->id,
            'amount' => 100.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Recalculate only campaign1
        $result = $this->service->recalculateTotalAmount($campaign1);

        expect($result)->toBeTrue()
            ->and($campaign1->fresh()->current_amount)->toBe('50.00')
            ->and($campaign2->fresh()->current_amount)->toBe('0.00');
    });

    test('handles large number of donations', function () {
        $campaign = Campaign::factory()->create(['current_amount' => 0]);

        // Create 100 successful donations of $10 each
        Donation::factory()->count(100)->create([
            'campaign_id' => $campaign->id,
            'amount' => 10.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        $result = $this->service->recalculateTotalAmount($campaign);

        expect($result)->toBeTrue()
            ->and($campaign->fresh()->current_amount)->toBe('1000.00');
    });

    test('is idempotent when called multiple times', function () {
        $campaign = Campaign::factory()->create(['current_amount' => 0]);

        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Call multiple times
        $this->service->recalculateTotalAmount($campaign);
        $this->service->recalculateTotalAmount($campaign);
        $this->service->recalculateTotalAmount($campaign);

        expect($campaign->fresh()->current_amount)->toBe('50.00');
    });
});
