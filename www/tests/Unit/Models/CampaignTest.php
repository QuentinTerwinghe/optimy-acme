<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\Currency;
use App\Models\Campaign\Campaign;
use App\Models\Auth\User;

describe('Campaign Model', function () {
    test('can create a campaign', function () {
        $campaign = Campaign::factory()->create([
            'title' => 'Save the Forest',
            'description' => 'Help us save the rainforest',
            'goal_amount' => 10000.00,
            'currency' => Currency::USD,
            'status' => CampaignStatus::DRAFT,
        ]);

        expect($campaign)->toBeInstanceOf(Campaign::class)
            ->and($campaign->title)->toBe('Save the Forest')
            ->and($campaign->description)->toBe('Help us save the rainforest')
            ->and($campaign->goal_amount)->toBe('10000.00')
            ->and($campaign->currency)->toBe(Currency::USD)
            ->and($campaign->status)->toBe(CampaignStatus::DRAFT);
    });

    test('uses UUID for primary key', function () {
        $campaign = Campaign::factory()->create();

        expect($campaign->id)->toBeString()
            ->and($campaign->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    });

    test('does not use auto-incrementing IDs', function () {
        $campaign = new Campaign();

        expect($campaign->incrementing)->toBeFalse()
            ->and($campaign->getKeyType())->toBe('string');
    });

    test('has correct fillable attributes', function () {
        $fillable = (new Campaign())->getFillable();

        expect($fillable)->toContain(
            'title',
            'description',
            'goal_amount',
            'current_amount',
            'currency',
            'start_date',
            'end_date',
            'status'
        );
    });

    test('casts attributes correctly', function () {
        $campaign = Campaign::factory()->create([
            'goal_amount' => 1000.50,
            'current_amount' => 500.25,
            'currency' => Currency::EUR,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => CampaignStatus::ACTIVE,
        ]);

        expect($campaign->goal_amount)->toBeString()
            ->and($campaign->current_amount)->toBeString()
            ->and($campaign->currency)->toBeInstanceOf(Currency::class)
            ->and($campaign->start_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($campaign->end_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($campaign->status)->toBeInstanceOf(CampaignStatus::class);
    });

    test('title is required', function () {
        expect(fn () => Campaign::factory()->create(['title' => null]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('goal_amount can be nullable for draft campaigns', function () {
        $campaign = Campaign::factory()->create([
            'goal_amount' => null,
            'status' => \App\Enums\CampaignStatus::DRAFT,
        ]);

        expect($campaign->goal_amount)->toBeNull();
    });

    test('currency can be nullable for draft campaigns', function () {
        $campaign = Campaign::factory()->create([
            'currency' => null,
            'status' => \App\Enums\CampaignStatus::DRAFT,
        ]);

        expect($campaign->currency)->toBeNull();
    });

    test('start_date can be nullable for draft campaigns', function () {
        $campaign = Campaign::factory()->create([
            'start_date' => null,
            'status' => \App\Enums\CampaignStatus::DRAFT,
        ]);

        expect($campaign->start_date)->toBeNull();
    });

    test('end_date can be nullable for draft campaigns', function () {
        $campaign = Campaign::factory()->create([
            'end_date' => null,
            'status' => \App\Enums\CampaignStatus::DRAFT,
        ]);

        expect($campaign->end_date)->toBeNull();
    });

    test('description can be nullable', function () {
        $campaign = Campaign::factory()->create(['description' => null]);

        expect($campaign->description)->toBeNull();
    });

    test('current_amount defaults to 0', function () {
        // Use the 'draft' state which sets current_amount to 0
        $campaign = Campaign::factory()->draft()->create();

        expect($campaign->current_amount)->toBe('0.00');
    });

    test('can create draft campaign', function () {
        $campaign = Campaign::factory()->draft()->create();

        expect($campaign->status)->toBe(CampaignStatus::DRAFT)
            ->and($campaign->current_amount)->toBe('0.00');
    });

    test('can create active campaign', function () {
        $campaign = Campaign::factory()->active()->create();

        expect($campaign->status)->toBe(CampaignStatus::ACTIVE)
            ->and($campaign->start_date)->toBeLessThanOrEqual(now())
            ->and($campaign->end_date)->toBeGreaterThan(now());
    });

    test('can create completed campaign', function () {
        $campaign = Campaign::factory()->completed()->create();

        expect($campaign->status)->toBe(CampaignStatus::COMPLETED)
            ->and($campaign->current_amount)->toBe($campaign->goal_amount);
    });

    test('can create cancelled campaign', function () {
        $campaign = Campaign::factory()->cancelled()->create();

        expect($campaign->status)->toBe(CampaignStatus::CANCELLED);
    });

    test('can set specific currency', function () {
        $campaign = Campaign::factory()->currency(Currency::GBP)->create();

        expect($campaign->currency)->toBe(Currency::GBP);
    });

    test('can set specific goal amount', function () {
        $campaign = Campaign::factory()->withGoal(50000.00)->create();

        expect($campaign->goal_amount)->toBe('50000.00');
    });

    test('can set specific current amount', function () {
        $campaign = Campaign::factory()->withCurrentAmount(25000.00)->create();

        expect($campaign->current_amount)->toBe('25000.00');
    });

    test('has HasTimestamps trait', function () {
        $campaign = Campaign::factory()->create();

        // Check that the custom timestamp columns are set
        expect($campaign->creation_date)->not->toBeNull()
            ->and($campaign->update_date)->not->toBeNull()
            ->and($campaign->creation_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($campaign->update_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    test('has HasUserTracking trait', function () {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->createdBy($user)->create();

        expect($campaign->created_by)->toBe($user->id)
            ->and($campaign->creator)->toBeInstanceOf(User::class)
            ->and($campaign->creator->id)->toBe($user->id);
    });

    test('getRouteKeyName returns id', function () {
        $campaign = new Campaign();

        expect($campaign->getRouteKeyName())->toBe('id');
    });

    test('uniqueIds returns id column', function () {
        $campaign = new Campaign();

        expect($campaign->uniqueIds())->toBe(['id']);
    });

    test('can find by UUID using scope', function () {
        $campaign = Campaign::factory()->create();
        $uuid = $campaign->id;

        $found = Campaign::findByUuid($uuid)->first();

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($uuid);
    });

    test('converts UUID string to binary for storage', function () {
        $campaign = Campaign::factory()->create();

        // The UUID should be stored and retrieved correctly
        expect($campaign->id)->toBeString()
            ->and(strlen($campaign->id))->toBe(36); // UUID format with dashes
    });

    test('timestamps are enabled', function () {
        $campaign = new Campaign();

        expect($campaign->timestamps)->toBeTrue();
    });

    test('factory can create multiple campaigns', function () {
        $campaigns = Campaign::factory()->count(5)->create();

        expect($campaigns)->toHaveCount(5)
            ->and($campaigns->pluck('id')->unique())->toHaveCount(5);
    });

    test('can track creator and updater', function () {
        $creator = User::factory()->create();
        $updater = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);

        expect($campaign->creator->id)->toBe($creator->id)
            ->and($campaign->updater->id)->toBe($updater->id);
    });

    test('creator and updater can be null', function () {
        $campaign = Campaign::factory()->create([
            'created_by' => null,
            'updated_by' => null,
        ]);

        expect($campaign->created_by)->toBeNull()
            ->and($campaign->updated_by)->toBeNull()
            ->and($campaign->creator)->toBeNull()
            ->and($campaign->updater)->toBeNull();
    });
});

describe('Campaign Model - Currency Handling', function () {
    test('supports USD currency', function () {
        $campaign = Campaign::factory()->currency(Currency::USD)->create();

        expect($campaign->currency)->toBe(Currency::USD)
            ->and($campaign->currency->value)->toBe('USD');
    });

    test('supports EUR currency', function () {
        $campaign = Campaign::factory()->currency(Currency::EUR)->create();

        expect($campaign->currency)->toBe(Currency::EUR)
            ->and($campaign->currency->value)->toBe('EUR');
    });

    test('supports GBP currency', function () {
        $campaign = Campaign::factory()->currency(Currency::GBP)->create();

        expect($campaign->currency)->toBe(Currency::GBP)
            ->and($campaign->currency->value)->toBe('GBP');
    });

    test('supports CHF currency', function () {
        $campaign = Campaign::factory()->currency(Currency::CHF)->create();

        expect($campaign->currency)->toBe(Currency::CHF)
            ->and($campaign->currency->value)->toBe('CHF');
    });

    test('supports CAD currency', function () {
        $campaign = Campaign::factory()->currency(Currency::CAD)->create();

        expect($campaign->currency)->toBe(Currency::CAD)
            ->and($campaign->currency->value)->toBe('CAD');
    });
});

describe('Campaign Model - Status Handling', function () {
    test('can be in draft status', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

        expect($campaign->status)->toBe(CampaignStatus::DRAFT)
            ->and($campaign->status->value)->toBe('draft');
    });

    test('can be in active status', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::ACTIVE]);

        expect($campaign->status)->toBe(CampaignStatus::ACTIVE)
            ->and($campaign->status->value)->toBe('active');
    });

    test('can be in completed status', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::COMPLETED]);

        expect($campaign->status)->toBe(CampaignStatus::COMPLETED)
            ->and($campaign->status->value)->toBe('completed');
    });

    test('can be in cancelled status', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::CANCELLED]);

        expect($campaign->status)->toBe(CampaignStatus::CANCELLED)
            ->and($campaign->status->value)->toBe('cancelled');
    });
});
