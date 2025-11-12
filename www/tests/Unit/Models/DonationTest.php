<?php

declare(strict_types=1);

use App\Enums\Donation\DonationStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;

describe('Donation Model', function () {
    test('can create a donation', function () {
        $campaign = Campaign::factory()->create();
        $user = User::factory()->create();

        $donation = Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'amount' => 100.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        expect($donation)->toBeInstanceOf(Donation::class)
            ->and($donation->campaign_id)->toBe($campaign->id)
            ->and($donation->user_id)->toBe($user->id)
            ->and($donation->amount)->toBe('100.00')
            ->and($donation->status)->toBe(DonationStatus::SUCCESS);
    });

    test('uses UUID for primary key', function () {
        $donation = Donation::factory()->create();

        expect($donation->id)->toBeString()
            ->and($donation->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    });

    test('does not use auto-incrementing IDs', function () {
        $donation = new Donation();

        expect($donation->incrementing)->toBeFalse()
            ->and($donation->getKeyType())->toBe('string');
    });

    test('has correct fillable attributes', function () {
        $fillable = (new Donation())->getFillable();

        expect($fillable)->toContain(
            'campaign_id',
            'user_id',
            'amount',
            'status',
            'error_message'
        );
    });

    test('casts attributes correctly', function () {
        $donation = Donation::factory()->create([
            'amount' => 100.50,
            'status' => DonationStatus::SUCCESS,
        ]);

        expect($donation->amount)->toBeString()
            ->and($donation->status)->toBeInstanceOf(DonationStatus::class)
            ->and($donation->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($donation->updated_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    test('campaign_id is required', function () {
        expect(fn () => Donation::factory()->create(['campaign_id' => null]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('user_id is required', function () {
        expect(fn () => Donation::factory()->create(['user_id' => null]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('amount is required', function () {
        expect(fn () => Donation::factory()->create(['amount' => null]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('status defaults to pending', function () {
        $donation = Donation::factory()->pending()->create();

        expect($donation->status)->toBe(DonationStatus::PENDING);
    });

    test('error_message can be nullable', function () {
        $donation = Donation::factory()->create(['error_message' => null]);

        expect($donation->error_message)->toBeNull();
    });

    test('can create pending donation', function () {
        $donation = Donation::factory()->pending()->create();

        expect($donation->status)->toBe(DonationStatus::PENDING)
            ->and($donation->error_message)->toBeNull();
    });

    test('can create successful donation', function () {
        $donation = Donation::factory()->successful()->create();

        expect($donation->status)->toBe(DonationStatus::SUCCESS)
            ->and($donation->error_message)->toBeNull();
    });

    test('can create failed donation', function () {
        $donation = Donation::factory()->failed()->create();

        expect($donation->status)->toBe(DonationStatus::FAILED)
            ->and($donation->error_message)->not->toBeNull();
    });

    test('can set specific campaign', function () {
        $campaign = Campaign::factory()->create();
        $donation = Donation::factory()->forCampaign($campaign)->create();

        expect($donation->campaign_id)->toBe($campaign->id);
    });

    test('can set specific user', function () {
        $user = User::factory()->create();
        $donation = Donation::factory()->byUser($user)->create();

        expect($donation->user_id)->toBe($user->id);
    });

    test('can set specific amount', function () {
        $donation = Donation::factory()->withAmount(250.00)->create();

        expect($donation->amount)->toBe('250.00');
    });

    test('can set specific error message', function () {
        $donation = Donation::factory()->withError('Payment declined')->create();

        expect($donation->status)->toBe(DonationStatus::FAILED)
            ->and($donation->error_message)->toBe('Payment declined');
    });

    test('has HasTimestamps trait', function () {
        $donation = Donation::factory()->create();

        expect($donation->created_at)->not->toBeNull()
            ->and($donation->updated_at)->not->toBeNull()
            ->and($donation->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($donation->updated_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    test('has HasUserTracking trait', function () {
        $user = User::factory()->create();
        $donation = Donation::factory()->createdBy($user)->create();

        expect($donation->created_by)->toBe($user->id)
            ->and($donation->creator)->toBeInstanceOf(User::class)
            ->and($donation->creator->id)->toBe($user->id);
    });

    test('getRouteKeyName returns id', function () {
        $donation = new Donation();

        expect($donation->getRouteKeyName())->toBe('id');
    });

    test('uniqueIds returns id column', function () {
        $donation = new Donation();

        expect($donation->uniqueIds())->toBe(['id']);
    });

    test('can find by UUID', function () {
        $donation = Donation::factory()->create();
        $uuid = $donation->id;

        $found = Donation::find($uuid);

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($uuid);
    });

    test('timestamps are enabled', function () {
        $donation = new Donation();

        expect($donation->timestamps)->toBeTrue();
    });

    test('factory can create multiple donations', function () {
        $donations = Donation::factory()->count(5)->create();

        expect($donations)->toHaveCount(5)
            ->and($donations->pluck('id')->unique())->toHaveCount(5);
    });

    test('can track creator and updater', function () {
        $creator = User::factory()->create();
        $updater = User::factory()->create();

        $donation = Donation::factory()->create([
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);

        expect($donation->creator->id)->toBe($creator->id)
            ->and($donation->updater->id)->toBe($updater->id);
    });

    test('creator and updater can be null', function () {
        $donation = Donation::factory()->create([
            'created_by' => null,
            'updated_by' => null,
        ]);

        expect($donation->created_by)->toBeNull()
            ->and($donation->updated_by)->toBeNull()
            ->and($donation->creator)->toBeNull()
            ->and($donation->updater)->toBeNull();
    });

    test('belongs to campaign', function () {
        $campaign = Campaign::factory()->create();
        $donation = Donation::factory()->forCampaign($campaign)->create();

        expect($donation->campaign)->toBeInstanceOf(Campaign::class)
            ->and($donation->campaign->id)->toBe($campaign->id);
    });

    test('belongs to user', function () {
        $user = User::factory()->create();
        $donation = Donation::factory()->byUser($user)->create();

        expect($donation->user)->toBeInstanceOf(User::class)
            ->and($donation->user->id)->toBe($user->id);
    });
});

describe('Donation Model - Status Handling', function () {
    test('can be in pending status', function () {
        $donation = Donation::factory()->create(['status' => DonationStatus::PENDING]);

        expect($donation->status)->toBe(DonationStatus::PENDING)
            ->and($donation->status->value)->toBe('pending');
    });

    test('can be in success status', function () {
        $donation = Donation::factory()->create(['status' => DonationStatus::SUCCESS]);

        expect($donation->status)->toBe(DonationStatus::SUCCESS)
            ->and($donation->status->value)->toBe('success');
    });

    test('can be in failed status', function () {
        $donation = Donation::factory()->create(['status' => DonationStatus::FAILED]);

        expect($donation->status)->toBe(DonationStatus::FAILED)
            ->and($donation->status->value)->toBe('failed');
    });

    test('isSuccessful returns true for successful donations', function () {
        $donation = Donation::factory()->successful()->create();

        expect($donation->isSuccessful())->toBeTrue();
    });

    test('isSuccessful returns false for non-successful donations', function () {
        $pendingDonation = Donation::factory()->pending()->create();
        $failedDonation = Donation::factory()->failed()->create();

        expect($pendingDonation->isSuccessful())->toBeFalse()
            ->and($failedDonation->isSuccessful())->toBeFalse();
    });

    test('hasFailed returns true for failed donations', function () {
        $donation = Donation::factory()->failed()->create();

        expect($donation->hasFailed())->toBeTrue();
    });

    test('hasFailed returns false for non-failed donations', function () {
        $pendingDonation = Donation::factory()->pending()->create();
        $successfulDonation = Donation::factory()->successful()->create();

        expect($pendingDonation->hasFailed())->toBeFalse()
            ->and($successfulDonation->hasFailed())->toBeFalse();
    });

    test('isPending returns true for pending donations', function () {
        $donation = Donation::factory()->pending()->create();

        expect($donation->isPending())->toBeTrue();
    });

    test('isPending returns false for non-pending donations', function () {
        $successfulDonation = Donation::factory()->successful()->create();
        $failedDonation = Donation::factory()->failed()->create();

        expect($successfulDonation->isPending())->toBeFalse()
            ->and($failedDonation->isPending())->toBeFalse();
    });
});

describe('Donation Model - Status Label', function () {
    test('status_label attribute returns correct label for pending', function () {
        $donation = Donation::factory()->pending()->create();

        expect($donation->status_label)->toBe('Pending');
    });

    test('status_label attribute returns correct label for success', function () {
        $donation = Donation::factory()->successful()->create();

        expect($donation->status_label)->toBe('Success');
    });

    test('status_label attribute returns correct label for failed', function () {
        $donation = Donation::factory()->failed()->create();

        expect($donation->status_label)->toBe('Failed');
    });

    test('toArray includes status_label', function () {
        $donation = Donation::factory()->successful()->create();
        $array = $donation->toArray();

        expect($array)->toHaveKey('status_label')
            ->and($array['status_label'])->toBe('Success');
    });
});
