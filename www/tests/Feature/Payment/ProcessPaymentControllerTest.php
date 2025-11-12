<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\Donation\DonationStatus;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for ProcessPaymentController
 */
class ProcessPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->campaign = Campaign::factory()->create();
    }

    public function test_initialize_payment_successfully(): void
    {
        // Arrange
        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 100.00,
            'payment_method' => PaymentMethodEnum::FAKE->value,
            'metadata' => ['source' => 'web'],
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Payment initialized successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'donation' => [
                        'id',
                        'campaign_id',
                        'amount',
                        'status',
                    ],
                    'payment' => [
                        'id',
                        'donation_id',
                        'amount',
                        'currency',
                        'payment_method',
                        'status',
                    ],
                ],
            ]);

        // Assert response data
        $responseData = $response->json('data');
        $this->assertEquals($this->campaign->id, $responseData['donation']['campaign_id']);
        $this->assertEquals(100.00, $responseData['donation']['amount']);
        $this->assertEquals(DonationStatus::PENDING->value, $responseData['donation']['status']);
        $this->assertEquals(PaymentMethodEnum::FAKE->value, $responseData['payment']['payment_method']);
        $this->assertEquals(PaymentStatusEnum::PENDING->value, $responseData['payment']['status']);

        // Assert database records
        $this->assertDatabaseHas('donations', [
            'id' => $responseData['donation']['id'],
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 100.00,
            'status' => DonationStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $responseData['payment']['id'],
            'donation_id' => $responseData['donation']['id'],
            'amount' => 100.00,
            'payment_method' => PaymentMethodEnum::FAKE->value,
            'status' => PaymentStatusEnum::PENDING->value,
        ]);
    }

    public function test_initialize_payment_requires_authentication(): void
    {
        // Arrange
        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 100.00,
            'payment_method' => PaymentMethodEnum::FAKE->value,
        ];

        // Act - Without authentication
        $response = $this->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(401);
    }

    public function test_initialize_payment_validates_required_fields(): void
    {
        // Act - Empty request
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['campaign_id', 'amount', 'payment_method']);
    }

    public function test_initialize_payment_validates_campaign_exists(): void
    {
        // Arrange
        $requestData = [
            'campaign_id' => 'non-existent-campaign-id',
            'amount' => 100.00,
            'payment_method' => PaymentMethodEnum::FAKE->value,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['campaign_id']);
    }

    public function test_initialize_payment_validates_minimum_amount(): void
    {
        // Arrange
        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 0.00, // Below minimum
            'payment_method' => PaymentMethodEnum::FAKE->value,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_initialize_payment_validates_maximum_amount(): void
    {
        // Arrange
        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 1000000.00, // Above maximum
            'payment_method' => PaymentMethodEnum::FAKE->value,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_initialize_payment_validates_payment_method(): void
    {
        // Arrange
        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 100.00,
            'payment_method' => 'invalid_payment_method',
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_initialize_payment_with_metadata(): void
    {
        // Arrange
        $metadata = [
            'source' => 'mobile_app',
            'device_id' => 'device123',
        ];

        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 50.00,
            'payment_method' => PaymentMethodEnum::FAKE->value,
            'metadata' => $metadata,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(201);

        $paymentId = $response->json('data.payment.id');
        $payment = Payment::find($paymentId);
        $this->assertEquals($metadata, $payment->metadata);
    }

    public function test_initialize_payment_without_metadata(): void
    {
        // Arrange
        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 50.00,
            'payment_method' => PaymentMethodEnum::FAKE->value,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(201);

        $paymentId = $response->json('data.payment.id');
        $payment = Payment::find($paymentId);
        $this->assertEquals([], $payment->metadata);
    }

    public function test_initialize_payment_creates_donation_linked_to_campaign(): void
    {
        // Arrange
        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 100.00,
            'payment_method' => PaymentMethodEnum::FAKE->value,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(201);

        $donationId = $response->json('data.donation.id');
        $donation = Donation::find($donationId);
        $this->assertNotNull($donation);
        $this->assertEquals($this->campaign->id, $donation->campaign_id);
        $this->assertEquals($this->user->id, $donation->user_id);
    }

    public function test_initialize_payment_creates_payment_linked_to_donation(): void
    {
        // Arrange
        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 100.00,
            'payment_method' => PaymentMethodEnum::FAKE->value,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(201);

        $donationId = $response->json('data.donation.id');
        $paymentId = $response->json('data.payment.id');

        $payment = Payment::find($paymentId);
        $this->assertNotNull($payment);
        $this->assertEquals($donationId, $payment->donation_id);
    }

    public function test_initialize_payment_returns_correct_currency(): void
    {
        // Arrange
        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 100.00,
            'payment_method' => PaymentMethodEnum::FAKE->value,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'payment' => [
                        'currency' => 'USD',
                    ],
                ],
            ]);
    }

    public function test_initialize_payment_with_different_payment_methods(): void
    {
        // Test with FAKE
        $requestData = [
            'campaign_id' => $this->campaign->id,
            'amount' => 100.00,
            'payment_method' => PaymentMethodEnum::FAKE->value,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'payment' => [
                        'payment_method' => PaymentMethodEnum::FAKE->value,
                    ],
                ],
            ]);

        // Test with PAYPAL (currently disabled but should still allow initialization)
        $requestData['payment_method'] = PaymentMethodEnum::PAYPAL->value;

        $response = $this->actingAs($this->user)
            ->postJson('/api/payments/initialize', $requestData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'payment' => [
                        'payment_method' => PaymentMethodEnum::PAYPAL->value,
                    ],
                ],
            ]);
    }
}
