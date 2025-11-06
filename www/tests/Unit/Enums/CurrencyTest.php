<?php

declare(strict_types=1);

use App\Enums\Currency;

describe('Currency Enum', function () {
    test('has all expected cases', function () {
        $cases = Currency::cases();

        expect($cases)->toHaveCount(5)
            ->and($cases)->toContain(Currency::USD)
            ->and($cases)->toContain(Currency::EUR)
            ->and($cases)->toContain(Currency::GBP)
            ->and($cases)->toContain(Currency::CHF)
            ->and($cases)->toContain(Currency::CAD);
    });

    test('USD case has correct value', function () {
        expect(Currency::USD->value)->toBe('USD');
    });

    test('EUR case has correct value', function () {
        expect(Currency::EUR->value)->toBe('EUR');
    });

    test('GBP case has correct value', function () {
        expect(Currency::GBP->value)->toBe('GBP');
    });

    test('CHF case has correct value', function () {
        expect(Currency::CHF->value)->toBe('CHF');
    });

    test('CAD case has correct value', function () {
        expect(Currency::CAD->value)->toBe('CAD');
    });

    test('values() returns all values', function () {
        $values = Currency::values();

        expect($values)->toBe(['USD', 'EUR', 'GBP', 'CHF', 'CAD'])
            ->and($values)->toHaveCount(5);
    });

    test('USD symbol returns correct character', function () {
        expect(Currency::USD->symbol())->toBe('$');
    });

    test('EUR symbol returns correct character', function () {
        expect(Currency::EUR->symbol())->toBe('€');
    });

    test('GBP symbol returns correct character', function () {
        expect(Currency::GBP->symbol())->toBe('£');
    });

    test('CHF symbol returns correct text', function () {
        expect(Currency::CHF->symbol())->toBe('CHF');
    });

    test('CAD symbol returns correct text', function () {
        expect(Currency::CAD->symbol())->toBe('CA$');
    });

    test('USD label returns correct text', function () {
        expect(Currency::USD->label())->toBe('US Dollar');
    });

    test('EUR label returns correct text', function () {
        expect(Currency::EUR->label())->toBe('Euro');
    });

    test('GBP label returns correct text', function () {
        expect(Currency::GBP->label())->toBe('British Pound');
    });

    test('CHF label returns correct text', function () {
        expect(Currency::CHF->label())->toBe('Swiss Franc');
    });

    test('CAD label returns correct text', function () {
        expect(Currency::CAD->label())->toBe('Canadian Dollar');
    });

    test('can be created from string value', function () {
        $currency = Currency::from('EUR');

        expect($currency)->toBe(Currency::EUR);
    });

    test('can be checked with tryFrom', function () {
        $validCurrency = Currency::tryFrom('GBP');
        $invalidCurrency = Currency::tryFrom('JPY');

        expect($validCurrency)->toBe(Currency::GBP)
            ->and($invalidCurrency)->toBeNull();
    });

    test('is backed by string', function () {
        expect(Currency::USD)->toBeInstanceOf(\BackedEnum::class);
    });

    test('all cases have unique values', function () {
        $values = array_map(fn ($case) => $case->value, Currency::cases());
        $uniqueValues = array_unique($values);

        expect($uniqueValues)->toHaveCount(count($values));
    });

    test('all cases have non-empty symbols', function () {
        foreach (Currency::cases() as $case) {
            expect($case->symbol())->not->toBeEmpty();
        }
    });

    test('all cases have non-empty labels', function () {
        foreach (Currency::cases() as $case) {
            expect($case->label())->not->toBeEmpty();
        }
    });

    test('can be used in match expression', function () {
        $currency = Currency::EUR;

        $result = match ($currency) {
            Currency::USD => 'american',
            Currency::EUR => 'european',
            Currency::GBP => 'british',
            Currency::CHF => 'swiss',
            Currency::CAD => 'canadian',
        };

        expect($result)->toBe('european');
    });

    test('can be compared with each other', function () {
        $usd = Currency::USD;
        $eur = Currency::EUR;

        expect($usd === Currency::USD)->toBeTrue()
            ->and($usd === $eur)->toBeFalse()
            ->and($usd !== $eur)->toBeTrue();
    });

    test('values follow ISO 4217 format', function () {
        foreach (Currency::cases() as $case) {
            expect($case->value)
                ->toBeString()
                ->toHaveLength(3)
                ->toMatch('/^[A-Z]{3}$/');
        }
    });

    test('can format amount with symbol', function () {
        $amount = 1234.56;

        expect(Currency::USD->symbol() . ' ' . number_format($amount, 2))->toBe('$ 1,234.56')
            ->and(Currency::EUR->symbol() . ' ' . number_format($amount, 2))->toBe('€ 1,234.56')
            ->and(Currency::GBP->symbol() . ' ' . number_format($amount, 2))->toBe('£ 1,234.56');
    });
});
