<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\Currency;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

describe('CampaignResource', function () {
    test('transforms campaign data correctly', function () {
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'description' => 'Test Description',
            'goal_amount' => 10000.00,
            'current_amount' => 5000.00,
            'currency' => Currency::USD,
            'status' => CampaignStatus::ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result)->toHaveKeys([
            'id',
            'title',
            'description',
            'goal_amount',
            'current_amount',
            'currency',
            'start_date',
            'end_date',
            'end_date_formatted',
            'status',
            'status_label',
            'progress_percentage',
            'days_remaining',
        ])->and($result['title'])->toBe('Test Campaign')
            ->and($result['description'])->toBe('Test Description')
            ->and($result['goal_amount'])->toBe('10000.00')
            ->and($result['current_amount'])->toBe('5000.00')
            ->and($result['currency'])->toBe('USD')
            ->and($result['status'])->toBe('active')
            ->and($result['status_label'])->toBe('Active');
    });

    test('formats goal_amount with two decimal places', function () {
        $campaign = Campaign::factory()->create([
            'goal_amount' => 1234.5,
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['goal_amount'])->toBe('1234.50');
    });

    test('formats current_amount with two decimal places', function () {
        $campaign = Campaign::factory()->create([
            'current_amount' => 987.1,
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['current_amount'])->toBe('987.10');
    });

    test('calculates progress_percentage correctly', function () {
        $campaign = Campaign::factory()->create([
            'goal_amount' => 10000.00,
            'current_amount' => 7500.00,
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['progress_percentage'])->toBe(75.0);
    });

    test('calculates progress_percentage for zero goal', function () {
        $campaign = Campaign::factory()->create([
            'goal_amount' => 0.00,
            'current_amount' => 100.00,
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['progress_percentage'])->toBe(0.0);
    });

    test('caps progress_percentage at 100', function () {
        $campaign = Campaign::factory()->create([
            'goal_amount' => 10000.00,
            'current_amount' => 15000.00,
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['progress_percentage'])->toBe(100.0);
    });

    test('calculates days_remaining correctly', function () {
        $campaign = Campaign::factory()->create([
            'end_date' => now()->addDays(5),
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['days_remaining'])->toBeGreaterThanOrEqual(4)
            ->and($result['days_remaining'])->toBeLessThanOrEqual(5);
    });

    test('returns zero days_remaining for past campaigns', function () {
        $campaign = Campaign::factory()->create([
            'end_date' => now()->subDays(5),
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['days_remaining'])->toBe(0);
    });

    test('formats end_date as ISO8601 string', function () {
        $endDate = now()->addWeek();
        $campaign = Campaign::factory()->create([
            'end_date' => $endDate,
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['end_date'])->toBeString()
            ->and($result['end_date'])->toContain('T')
            ->and($result['end_date'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    });

    test('formats end_date_formatted as readable date', function () {
        $campaign = Campaign::factory()->create([
            'end_date' => now()->parse('2025-12-25'),
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['end_date_formatted'])->toBe('Dec 25, 2025');
    });

    test('includes all currency types', function () {
        foreach ([Currency::USD, Currency::EUR, Currency::GBP, Currency::CHF, Currency::CAD] as $currency) {
            $campaign = Campaign::factory()->create([
                'currency' => $currency,
            ]);

            $resource = new CampaignResource($campaign);
            $result = $resource->toArray(new Request());

            expect($result['currency'])->toBe($currency->value);
        }
    });

    test('includes all status types', function () {
        $statuses = [
            ['status' => CampaignStatus::DRAFT, 'label' => 'Draft'],
            ['status' => CampaignStatus::ACTIVE, 'label' => 'Active'],
            ['status' => CampaignStatus::COMPLETED, 'label' => 'Completed'],
            ['status' => CampaignStatus::CANCELLED, 'label' => 'Cancelled'],
        ];

        foreach ($statuses as $statusData) {
            $campaign = Campaign::factory()->create([
                'status' => $statusData['status'],
            ]);

            $resource = new CampaignResource($campaign);
            $result = $resource->toArray(new Request());

            expect($result['status'])->toBe($statusData['status']->value)
                ->and($result['status_label'])->toBe($statusData['label']);
        }
    });

    test('handles null description', function () {
        $campaign = Campaign::factory()->create([
            'description' => null,
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['description'])->toBeNull();
    });

    test('handles campaign with no current amount', function () {
        $campaign = Campaign::factory()->create([
            'current_amount' => 0.00,
        ]);

        $resource = new CampaignResource($campaign);
        $result = $resource->toArray(new Request());

        expect($result['current_amount'])->toBe('0.00')
            ->and($result['progress_percentage'])->toBe(0.0);
    });

    test('resource collection works correctly', function () {
        $campaigns = Campaign::factory()->count(3)->create();

        $collection = CampaignResource::collection($campaigns);
        $result = $collection->toArray(new Request());

        expect($result)->toHaveCount(3)
            ->and($result[0])->toHaveKey('id')
            ->and($result[1])->toHaveKey('title')
            ->and($result[2])->toHaveKey('progress_percentage');
    });
});
