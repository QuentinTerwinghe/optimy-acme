<?php

declare(strict_types=1);

use App\Enums\Campaign\CampaignStatus;

describe('CampaignStatus Enum', function () {
    test('has all expected cases', function () {
        $cases = CampaignStatus::cases();

        expect($cases)->toHaveCount(6)
            ->and($cases)->toContain(CampaignStatus::DRAFT)
            ->and($cases)->toContain(CampaignStatus::WAITING_FOR_VALIDATION)
            ->and($cases)->toContain(CampaignStatus::ACTIVE)
            ->and($cases)->toContain(CampaignStatus::REJECTED)
            ->and($cases)->toContain(CampaignStatus::COMPLETED)
            ->and($cases)->toContain(CampaignStatus::CANCELLED);
    });

    test('draft case has correct value', function () {
        expect(CampaignStatus::DRAFT->value)->toBe('draft');
    });

    test('active case has correct value', function () {
        expect(CampaignStatus::ACTIVE->value)->toBe('active');
    });

    test('completed case has correct value', function () {
        expect(CampaignStatus::COMPLETED->value)->toBe('completed');
    });

    test('rejected case has correct value', function () {
        expect(CampaignStatus::REJECTED->value)->toBe('rejected');
    });

    test('cancelled case has correct value', function () {
        expect(CampaignStatus::CANCELLED->value)->toBe('cancelled');
    });

    test('values() returns all values', function () {
        $values = CampaignStatus::values();

        expect($values)->toBe(['draft', 'waiting_for_validation', 'active', 'rejected', 'completed', 'cancelled'])
            ->and($values)->toHaveCount(6);
    });

    test('draft label returns correct text', function () {
        expect(CampaignStatus::DRAFT->label())->toBe('Draft');
    });

    test('active label returns correct text', function () {
        expect(CampaignStatus::ACTIVE->label())->toBe('Active');
    });

    test('rejected label returns correct text', function () {
        expect(CampaignStatus::REJECTED->label())->toBe('Rejected');
    });

    test('completed label returns correct text', function () {
        expect(CampaignStatus::COMPLETED->label())->toBe('Completed');
    });

    test('cancelled label returns correct text', function () {
        expect(CampaignStatus::CANCELLED->label())->toBe('Cancelled');
    });

    test('can be created from string value', function () {
        $status = CampaignStatus::from('active');

        expect($status)->toBe(CampaignStatus::ACTIVE);
    });

    test('can be checked with tryFrom', function () {
        $validStatus = CampaignStatus::tryFrom('draft');
        $invalidStatus = CampaignStatus::tryFrom('invalid');

        expect($validStatus)->toBe(CampaignStatus::DRAFT)
            ->and($invalidStatus)->toBeNull();
    });

    test('is backed by string', function () {
        expect(CampaignStatus::DRAFT)->toBeInstanceOf(\BackedEnum::class);
    });

    test('all cases have unique values', function () {
        $values = array_map(fn ($case) => $case->value, CampaignStatus::cases());
        $uniqueValues = array_unique($values);

        expect($uniqueValues)->toHaveCount(count($values));
    });

    test('all cases have non-empty labels', function () {
        foreach (CampaignStatus::cases() as $case) {
            expect($case->label())->not->toBeEmpty();
        }
    });

    test('can be used in match expression', function () {
        $status = CampaignStatus::ACTIVE;

        $result = match ($status) {
            CampaignStatus::DRAFT => 'not_started',
            CampaignStatus::WAITING_FOR_VALIDATION => 'pending',
            CampaignStatus::ACTIVE => 'running',
            CampaignStatus::REJECTED => 'denied',
            CampaignStatus::COMPLETED => 'finished',
            CampaignStatus::CANCELLED => 'stopped',
        };

        expect($result)->toBe('running');
    });

    test('can be compared with each other', function () {
        $draft = CampaignStatus::DRAFT;
        $active = CampaignStatus::ACTIVE;

        expect($draft === CampaignStatus::DRAFT)->toBeTrue()
            ->and($draft === $active)->toBeFalse()
            ->and($draft !== $active)->toBeTrue();
    });
});
