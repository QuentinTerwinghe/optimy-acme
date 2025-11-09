# Testing Guide

This document provides comprehensive information about testing in the ACME Corp Laravel project using Pest PHP.

## Table of Contents

- [Overview](#overview)
- [Testing Framework](#testing-framework)
- [Running Tests](#running-tests)
- [Test Structure](#test-structure)
- [Writing Tests](#writing-tests)
- [Factories](#factories)
- [Best Practices](#best-practices)
- [Code Coverage](#code-coverage)
- [Continuous Integration](#continuous-integration)

## Overview

This project uses **Pest PHP**, a modern testing framework built on top of PHPUnit, providing an elegant and developer-friendly testing experience. Pest offers:

- Beautiful and expressive syntax
- Better error messages
- Architecture testing capabilities
- Mutation testing support
- Parallel test execution
- Built-in Laravel support

### Current Test Statistics

- **Total Tests**: 193 passing tests
- **Total Assertions**: 510
- **Test Coverage**: Comprehensive coverage across all layers
  - Unit Tests: Models, Services, Enums, DTOs, Resources
  - Feature Tests: API endpoints, Campaign flows, Authentication
- **Test Organization**: Organized by domain following Pseudo-DDD structure
- **PHPStan Status**: ✅ 0 errors (Level 9)

## Testing Framework

### Pest PHP

- **Version**: 3.8.4
- **Plugin**: Laravel Plugin 3.2.0
- **Documentation**: [https://pestphp.com](https://pestphp.com)

### Additional Tools

- **PHPUnit**: 11.5.33 (underlying test runner)
- **PHPStan**: 2.1+ (static analysis)
- **Larastan**: 3.8+ (PHPStan for Laravel)

## Running Tests

### Using Make Commands (Recommended)

All test commands are available through the Makefile for consistency and ease of use.

#### Basic Commands

```bash
# Run all tests
make test

# Run only unit tests
make test-unit

# Run only feature tests
make test-feature
```

#### Advanced Commands

```bash
# Run specific test file or class
make test-filter F="CampaignTest"
make test-filter F="CurrencyTest"

# Run tests with code coverage analysis
make test-coverage

# Run Pest directly (all tests)
make pest

# Run Pest with custom arguments
make pest ARGS="--parallel"
make pest ARGS="--compact"
make pest ARGS="--filter=Campaign"
```

### Using Artisan (Alternative)

```bash
# Inside the Docker container
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test --filter=CampaignTest
```

### Using Pest Directly (Alternative)

```bash
# Inside the Docker container
./vendor/bin/pest

# Run with parallel execution
./vendor/bin/pest --parallel

# Run with compact output
./vendor/bin/pest --compact

# Run specific directory
./vendor/bin/pest tests/Unit/Models

# Run with coverage
./vendor/bin/pest --coverage --min=80
```

## Test Structure

### Directory Organization

Following the **Pseudo-DDD** pattern, tests are organized by domain:

```text
www/tests/
├── Feature/                        # Integration and HTTP tests
│   ├── Api/                        # API endpoint tests
│   │   └── CampaignControllerTest.php
│   └── Campaign/                   # Campaign domain feature tests
│       └── CampaignStoreTest.php
├── Unit/                           # Unit tests for individual components
│   ├── ConfigTest.php              # Configuration tests
│   ├── DataTransferObjects/       # DTO tests
│   │   └── NotificationPayloadTest.php
│   ├── Enums/                      # Enum tests
│   │   ├── CampaignStatusTest.php
│   │   └── CurrencyTest.php
│   ├── Models/                     # Model tests
│   │   ├── CampaignTest.php
│   │   └── UserTest.php
│   ├── Resources/                  # API resource tests
│   │   └── CampaignResourceTest.php
│   └── Services/                   # Service tests
│       ├── CampaignServiceTest.php
│       └── Notifications/          # Notification service tests
│           ├── AbstractNotificationHandlerTest.php
│           ├── NotificationRegistryTest.php
│           ├── NotificationServiceTest.php
│           └── Handlers/
│               └── ForgotPasswordHandlerTest.php
├── Pest.php                        # Pest configuration
└── TestCase.php                    # Base test case
```

### Test Categories

#### Unit Tests (`tests/Unit/`)

Test individual classes, methods, and components in isolation.

**Current Unit Tests** (180 tests):
- Config: 3 tests
- Data Transfer Objects: 12 tests
- Enums: 44 tests (CampaignStatus, Currency)
- Models: 51 tests (Campaign, User)
- Resources: 15 tests (CampaignResource)
- Services: 23 tests (Campaign services, Notification system)
  - AbstractNotificationHandler: 6 tests
  - NotificationRegistry: 6 tests
  - NotificationService: 5 tests
  - ForgotPasswordHandler: 10 tests

#### Feature Tests (`tests/Feature/`)

Test complete features, HTTP endpoints, and user workflows (32 tests).

**Current Feature Tests**:
- API Endpoints: 22 tests (Campaign API, authentication, edge cases)
- Campaign Management: 10 tests (Create campaigns, validation, tags/categories)

## Writing Tests

### Pest Syntax Basics

Pest uses a functional, expressive syntax:

```php
<?php

use App\Models\User;

describe('User Model', function () {
    test('can create a user', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('John Doe')
            ->and($user->email)->toBe('john@example.com');
    });

    test('uses UUID for primary key', function () {
        $user = User::factory()->create();

        expect($user->id)->toBeString()
            ->and($user->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-/');
    });
});
```

### Expectations

Pest provides powerful expectation methods:

```php
// Type expectations
expect($value)->toBeString();
expect($value)->toBeInt();
expect($value)->toBeBool();
expect($value)->toBeArray();
expect($value)->toBeInstanceOf(User::class);

// Value expectations
expect($value)->toBe('expected');
expect($value)->toEqual(['key' => 'value']);
expect($value)->toBeNull();
expect($value)->toBeEmpty();
expect($value)->toBeTrue();
expect($value)->toBeFalse();

// Comparison expectations
expect($value)->toBeGreaterThan(10);
expect($value)->toBeLessThan(100);
expect($value)->toBeGreaterThanOrEqual(10);
expect($value)->toBeLessThanOrEqual(100);

// String expectations
expect($value)->toContain('substring');
expect($value)->toStartWith('prefix');
expect($value)->toEndWith('suffix');
expect($value)->toMatch('/regex/');

// Array expectations
expect($array)->toHaveCount(5);
expect($array)->toContain('value');
expect($array)->toHaveKey('key');

// Collection expectations (Laravel)
expect($collection)->toHaveCount(10);
expect($collection->first())->toBeInstanceOf(User::class);
```

### Exception Testing

```php
test('throws exception when invalid data provided', function () {
    expect(fn () => User::create(['invalid' => 'data']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

### Database Testing

For tests that interact with the database, use the `RefreshDatabase` trait (automatically applied to Feature tests):

```php
<?php

use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can persist campaign to database', function () {
    $campaign = Campaign::factory()->create([
        'title' => 'Test Campaign'
    ]);

    expect($campaign->exists)->toBeTrue();

    $this->assertDatabaseHas('campaigns', [
        'title' => 'Test Campaign'
    ]);
});
```

## Factories

Factories provide a convenient way to generate test data. All factories are located in `www/database/factories/`.

### Using Factories

#### User Factory

```php
use App\Models\User;

// Create a single user
$user = User::factory()->create();

// Create with specific attributes
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Create unverified user
$user = User::factory()->unverified()->create();

// Create multiple users
$users = User::factory()->count(10)->create();
```

#### Campaign Factory

The Campaign factory includes multiple states and helper methods:

```php
use App\Models\Campaign;
use App\Enums\Common\Currency;

// Basic campaign creation
$campaign = Campaign::factory()->create();

// Campaign states
$draft = Campaign::factory()->draft()->create();
$active = Campaign::factory()->active()->create();
$completed = Campaign::factory()->completed()->create();
$cancelled = Campaign::factory()->cancelled()->create();

// With specific attributes
$campaign = Campaign::factory()
    ->currency(Currency::EUR)
    ->withGoal(50000.00)
    ->withCurrentAmount(25000.00)
    ->create();

// Campaign with creator
$user = User::factory()->create();
$campaign = Campaign::factory()
    ->createdBy($user)
    ->create();

// Goal reached campaign
$campaign = Campaign::factory()->goalReached()->create();
```

### Creating New Factories

Generate a new factory:

```bash
make artisan C="make:factory PostFactory"
```

Example factory structure:

```php
<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'published' => fake()->boolean(),
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the post is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published' => false,
            'published_at' => null,
        ]);
    }
}
```

## Best Practices

### 1. Test Organization

- **Group related tests** using `describe()` blocks
- **Use descriptive test names** that explain what is being tested
- **One assertion per test** when possible
- **Keep tests independent** - tests should not depend on each other

```php
describe('Campaign Status', function () {
    test('can be in draft status', function () {
        $campaign = Campaign::factory()->draft()->create();

        expect($campaign->status)->toBe(CampaignStatus::DRAFT);
    });

    test('can transition from draft to active', function () {
        $campaign = Campaign::factory()->draft()->create();
        $campaign->status = CampaignStatus::ACTIVE;
        $campaign->save();

        expect($campaign->fresh()->status)->toBe(CampaignStatus::ACTIVE);
    });
});
```

### 2. Factory Usage

- **Always use factories** for creating test data
- **Create reusable factory states** for common scenarios
- **Use meaningful factory methods** that express intent

```php
// Good: Expressive and clear
$campaign = Campaign::factory()->active()->goalReached()->create();

// Avoid: Manual data creation
$campaign = new Campaign([
    'status' => 'active',
    'goal_amount' => 10000,
    'current_amount' => 10000,
    // ... many more fields
]);
```

### 3. Assertion Style

- **Use Pest expectations** over PHPUnit assertions
- **Chain expectations** for related checks
- **Be specific** with assertions

```php
// Good: Pest expectations
expect($user->id)->toBeString()
    ->and($user->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-/');

// Also good: PHPUnit assertions (when needed)
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
```

### 4. Test Data Isolation

- **Use RefreshDatabase** for database tests
- **Don't rely on seeded data** in tests
- **Clean up after tests** if creating external resources

```php
uses(RefreshDatabase::class);

test('creates campaign correctly', function () {
    // Database is fresh for this test
    $campaign = Campaign::factory()->create();

    expect(Campaign::count())->toBe(1);
});
```

### 5. Test Naming

Use clear, descriptive names that explain:
- What is being tested
- Under what conditions
- What the expected outcome is

```php
// Good test names
test('can create a user');
test('throws exception when email is invalid');
test('returns 404 when campaign not found');
test('updates campaign status to completed when goal is reached');

// Avoid vague names
test('user test');
test('it works');
test('test_1');
```

## Code Coverage

### Running Coverage Analysis

```bash
# Using Make
make test-coverage

# Using Pest directly
./vendor/bin/pest --coverage

# With minimum coverage threshold
./vendor/bin/pest --coverage --min=80

# Coverage with HTML report
./vendor/bin/pest --coverage --coverage-html=coverage-report
```

### Coverage Requirements

- **Minimum Coverage**: 80% (enforced by `make test-coverage`)
- **Target Coverage**: 90%+ for business logic
- **Focus Areas**: Models, Services, Critical business logic

### Viewing Coverage Reports

```bash
# Generate HTML report
make pest ARGS="--coverage-html=coverage"

# Open in browser (macOS)
open coverage/index.html

# Open in browser (Linux)
xdg-open coverage/index.html
```

## Continuous Integration

### Pre-Commit Checks

Before committing code, run:

```bash
# Run all tests
make test

# Run static analysis
make phpstan

# Run code style fixer
make artisan C="pint"
```

### CI/CD Pipeline

Recommended checks for CI/CD:

```yaml
# Example GitHub Actions workflow
- name: Run Tests
  run: make test

- name: Run PHPStan
  run: make phpstan

- name: Check Code Coverage
  run: make test-coverage

- name: Check Code Style
  run: make artisan C="pint --test"
```

## Troubleshooting

### Common Issues

#### Database Connection Errors

If you see "Database file at path [acme_corp] does not exist":

```bash
# Clear config cache
make artisan C="config:clear"

# Run tests again
make test
```

#### Memory Issues

For large test suites:

```bash
# Increase PHP memory limit
make pest ARGS="--memory-limit=512M"
```

#### Parallel Execution Issues

If tests fail when running in parallel:

```bash
# Run without parallelization
make pest ARGS="--processes=1"
```

### Getting Help

- **Pest Documentation**: [https://pestphp.com/docs](https://pestphp.com/docs)
- **Laravel Testing**: [https://laravel.com/docs/12.x/testing](https://laravel.com/docs/12.x/testing)
- **Project Issues**: Check existing tests for examples

## Examples

### Testing Enums

```php
<?php

use App\Enums\Common\Currency;

describe('Currency Enum', function () {
    test('has all expected cases', function () {
        $cases = Currency::cases();

        expect($cases)->toHaveCount(5)
            ->and($cases)->toContain(Currency::USD)
            ->and($cases)->toContain(Currency::EUR);
    });

    test('returns correct symbol', function () {
        expect(Currency::USD->symbol())->toBe('$')
            ->and(Currency::EUR->symbol())->toBe('€');
    });

    test('can be created from string', function () {
        $currency = Currency::from('USD');

        expect($currency)->toBe(Currency::USD);
    });
});
```

### Testing Models

```php
<?php

use App\Models\Campaign;
use App\Enums\Campaign\CampaignStatus;

describe('Campaign Model', function () {
    test('uses UUID for primary key', function () {
        $campaign = Campaign::factory()->create();

        expect($campaign->id)->toBeString()
            ->and($campaign->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-/');
    });

    test('casts status to enum', function () {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE
        ]);

        expect($campaign->status)->toBeInstanceOf(CampaignStatus::class)
            ->and($campaign->status)->toBe(CampaignStatus::ACTIVE);
    });
});
```

### Testing HTTP Endpoints

```php
<?php

use App\Models\User;
use App\Models\Campaign;

describe('Campaign API', function () {
    test('can list campaigns', function () {
        Campaign::factory()->count(5)->create();

        $response = $this->getJson('/api/campaigns');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    });

    test('can create campaign', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/campaigns', [
                'title' => 'New Campaign',
                'goal_amount' => 10000,
                'currency' => 'USD',
                'start_date' => now(),
                'end_date' => now()->addMonth(),
            ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'title' => 'New Campaign'
                ]
            ]);
    });
});
```

## Summary

This project uses Pest PHP for comprehensive testing, providing:

- ✅ **193 passing tests** with **510 assertions**
- ✅ **Organized by domain** following Pseudo-DDD structure
- ✅ **Comprehensive coverage**: Models, Services, Enums, DTOs, Resources, APIs
- ✅ **Factory support** for efficient test data generation
- ✅ **Easy-to-use Make commands** for running tests
- ✅ **PHPStan Level 9** for static analysis
- ✅ **Code coverage support** to measure test effectiveness

**Test Breakdown**:
- Unit Tests: 180 tests (Config, DTOs, Enums, Models, Services, Resources)
- Feature Tests: 32 tests (API endpoints, Campaign flows)
- Zero errors in static analysis

**Quick Commands**:
```bash
make test              # Run all tests (193 tests)
make test-unit         # Run unit tests only
make test-feature      # Run feature tests only
make test-coverage     # Run with coverage analysis
make phpstan           # Run static analysis (Level 9)
```

**Testing Philosophy**:
- Write tests before code (TDD)
- All new features must have tests
- Never commit with failing tests
- Maintain high test coverage
- Follow SOLID principles in tests

For more information, see:
- [Main README](../README.md) - Project overview
- [Architecture Guide](ARCHITECTURE.md) - Project structure and patterns
- Explore existing test files in `www/tests/` for examples
