<?php

declare(strict_types=1);

use App\Models\Campaign\Campaign;
use App\Models\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Campaign API - getActiveCampaigns', function () {
    test('requires authentication', function () {
        $response = $this->getJson('/api/campaigns/active');

        $response->assertStatus(401);
    });

    test('returns active campaigns for authenticated user', function () {
        $user = User::factory()->create();
        Campaign::factory()->active()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
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
                    ],
                ],
            ])->assertJsonCount(3, 'data');
    });

    test('returns only active campaigns', function () {
        $user = User::factory()->create();

        // Create campaigns with different statuses
        Campaign::factory()->draft()->create();
        Campaign::factory()->completed()->create();
        Campaign::factory()->cancelled()->create();
        $activeCampaign = Campaign::factory()->active()->create([
            'title' => 'Active Campaign',
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Active Campaign')
            ->assertJsonPath('data.0.status', 'active');
    });

    test('returns only campaigns with future end dates', function () {
        $user = User::factory()->create();

        // Create active campaign that has ended
        Campaign::factory()->active()->create([
            'title' => 'Ended Campaign',
            'end_date' => now()->subDay(),
        ]);

        // Create active campaign that ends in the future
        Campaign::factory()->active()->create([
            'title' => 'Future Campaign',
            'end_date' => now()->addWeek(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Future Campaign');
    });

    test('returns campaigns ordered by end_date ascending', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create([
            'title' => 'Campaign 3',
            'end_date' => now()->addWeeks(3),
        ]);
        Campaign::factory()->active()->create([
            'title' => 'Campaign 1',
            'end_date' => now()->addWeek(),
        ]);
        Campaign::factory()->active()->create([
            'title' => 'Campaign 2',
            'end_date' => now()->addWeeks(2),
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.title', 'Campaign 1')
            ->assertJsonPath('data.1.title', 'Campaign 2')
            ->assertJsonPath('data.2.title', 'Campaign 3');
    });

    test('returns empty array when no active campaigns', function () {
        $user = User::factory()->create();

        Campaign::factory()->draft()->create();
        Campaign::factory()->completed()->create();

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    test('includes correct progress_percentage calculation', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create([
            'goal_amount' => 10000.00,
            'current_amount' => 7500.00,
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200);

        expect((float) $response->json('data.0.progress_percentage'))->toBe(75.0);
    });

    test('includes correct days_remaining calculation', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create([
            'end_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200);

        $daysRemaining = $response->json('data.0.days_remaining');
        expect($daysRemaining)->toBeGreaterThanOrEqual(4)
            ->and($daysRemaining)->toBeLessThanOrEqual(5);
    });

    test('includes formatted end date', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create([
            'end_date' => now()->parse('2025-12-25'),
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.end_date_formatted', 'Dec 25, 2025');
    });

    test('includes status label', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create();

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('data.0.status_label', 'Active');
    });

    test('handles campaign with null description', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create([
            'description' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.description', null);
    });

    test('formats amounts with two decimal places', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create([
            'goal_amount' => 1234.5,
            'current_amount' => 987.1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.goal_amount', '1234.50')
            ->assertJsonPath('data.0.current_amount', '987.10');
    });

    test('returns all currency types correctly', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create(['currency' => \App\Enums\Common\Currency::USD]);
        Campaign::factory()->active()->create(['currency' => \App\Enums\Common\Currency::EUR]);
        Campaign::factory()->active()->create(['currency' => \App\Enums\Common\Currency::GBP]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $currencies = collect($response->json('data'))->pluck('currency')->sort()->values();
        expect($currencies->toArray())->toContain('EUR', 'GBP', 'USD');
    });

    test('handles large number of campaigns', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->count(50)->create();

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonCount(50, 'data');
    });

    test('returns valid JSON structure', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create();

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200)
            ->assertJsonIsObject()
            ->assertJsonStructure(['data']);
    });

    test('campaign IDs are UUIDs', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create();

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200);

        $id = $response->json('data.0.id');
        expect($id)->toBeString()
            ->and($id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    });

    test('date fields are ISO8601 formatted', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create();

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200);

        $startDate = $response->json('data.0.start_date');
        $endDate = $response->json('data.0.end_date');

        expect($startDate)->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/')
            ->and($endDate)->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    });
});

describe('Campaign API - Authorization', function () {
    test('guest users cannot access endpoint', function () {
        Campaign::factory()->active()->create();

        $response = $this->getJson('/api/campaigns/active');

        $response->assertStatus(401);
    });

    test('authenticated users can access endpoint', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200);
    });
});

describe('Campaign API - Edge Cases', function () {
    test('handles campaign ending exactly now', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create([
            'end_date' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200);
        // Campaign ending exactly now should not be included (must be future)
        expect($response->json('data'))->toBeEmpty();
    });

    test('handles campaign with progress over 100%', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create([
            'goal_amount' => 1000.00,
            'current_amount' => 1500.00,
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200);

        expect((float) $response->json('data.0.progress_percentage'))->toBe(100.0);
    });

    test('handles campaign with zero goal amount', function () {
        $user = User::factory()->create();

        Campaign::factory()->active()->create([
            'goal_amount' => 0.00,
            'current_amount' => 100.00,
        ]);

        $response = $this->actingAs($user)->getJson('/api/campaigns/active');

        $response->assertStatus(200);

        expect((float) $response->json('data.0.progress_percentage'))->toBe(0.0);
    });
});
