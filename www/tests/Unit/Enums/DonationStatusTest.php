<?php

declare(strict_types=1);

use App\Enums\Donation\DonationStatus;

describe('DonationStatus Enum', function () {
    test('has all expected cases', function () {
        $cases = DonationStatus::cases();

        expect($cases)->toHaveCount(3)
            ->and($cases)->toContain(DonationStatus::PENDING)
            ->and($cases)->toContain(DonationStatus::SUCCESS)
            ->and($cases)->toContain(DonationStatus::FAILED);
    });

    test('pending case has correct value', function () {
        expect(DonationStatus::PENDING->value)->toBe('pending');
    });

    test('success case has correct value', function () {
        expect(DonationStatus::SUCCESS->value)->toBe('success');
    });

    test('failed case has correct value', function () {
        expect(DonationStatus::FAILED->value)->toBe('failed');
    });

    test('values() returns all values', function () {
        $values = DonationStatus::values();

        expect($values)->toBe(['pending', 'success', 'failed'])
            ->and($values)->toHaveCount(3);
    });

    test('pending label returns correct text', function () {
        expect(DonationStatus::PENDING->label())->toBe('Pending');
    });

    test('success label returns correct text', function () {
        expect(DonationStatus::SUCCESS->label())->toBe('Success');
    });

    test('failed label returns correct text', function () {
        expect(DonationStatus::FAILED->label())->toBe('Failed');
    });

    test('can be created from string value', function () {
        $status = DonationStatus::from('success');

        expect($status)->toBe(DonationStatus::SUCCESS);
    });

    test('can be checked with tryFrom', function () {
        $validStatus = DonationStatus::tryFrom('pending');
        $invalidStatus = DonationStatus::tryFrom('invalid');

        expect($validStatus)->toBe(DonationStatus::PENDING)
            ->and($invalidStatus)->toBeNull();
    });

    test('is backed by string', function () {
        expect(DonationStatus::PENDING)->toBeInstanceOf(\BackedEnum::class);
    });

    test('all cases have unique values', function () {
        $values = array_map(fn ($case) => $case->value, DonationStatus::cases());
        $uniqueValues = array_unique($values);

        expect($uniqueValues)->toHaveCount(count($values));
    });

    test('all cases have non-empty labels', function () {
        foreach (DonationStatus::cases() as $case) {
            expect($case->label())->not->toBeEmpty();
        }
    });

    test('can be used in match expression', function () {
        $status = DonationStatus::SUCCESS;

        $result = match ($status) {
            DonationStatus::PENDING => 'processing',
            DonationStatus::SUCCESS => 'completed',
            DonationStatus::FAILED => 'error',
        };

        expect($result)->toBe('completed');
    });

    test('can be compared with each other', function () {
        $pending = DonationStatus::PENDING;
        $success = DonationStatus::SUCCESS;

        expect($pending === DonationStatus::PENDING)->toBeTrue()
            ->and($pending === $success)->toBeFalse()
            ->and($pending !== $success)->toBeTrue();
    });
});

describe('DonationStatus Enum - Helper Methods', function () {
    test('isSuccessful returns true for success status', function () {
        expect(DonationStatus::SUCCESS->isSuccessful())->toBeTrue();
    });

    test('isSuccessful returns false for non-success statuses', function () {
        expect(DonationStatus::PENDING->isSuccessful())->toBeFalse()
            ->and(DonationStatus::FAILED->isSuccessful())->toBeFalse();
    });

    test('hasFailed returns true for failed status', function () {
        expect(DonationStatus::FAILED->hasFailed())->toBeTrue();
    });

    test('hasFailed returns false for non-failed statuses', function () {
        expect(DonationStatus::PENDING->hasFailed())->toBeFalse()
            ->and(DonationStatus::SUCCESS->hasFailed())->toBeFalse();
    });

    test('isPending returns true for pending status', function () {
        expect(DonationStatus::PENDING->isPending())->toBeTrue();
    });

    test('isPending returns false for non-pending statuses', function () {
        expect(DonationStatus::SUCCESS->isPending())->toBeFalse()
            ->and(DonationStatus::FAILED->isPending())->toBeFalse();
    });
});
