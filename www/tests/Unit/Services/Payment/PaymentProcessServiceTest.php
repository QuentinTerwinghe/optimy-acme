<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\Enums\Donation\DonationStatus;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Exceptions\Payment\PaymentProcessingException;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use App\Services\Payment\PaymentProcessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unit tests for PaymentProcessService
 */
class PaymentProcessServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentProcessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentProcessService();
    }

    public function test_initialize_payment_creates_donation_and_payment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();
        $amount = 100.00;
        $paymentMethod = PaymentMethodEnum::FAKE;

        // Act
        $result = $this->service->initializePayment(
            campaignId: $campaign->id,
            userId: $user->id,
            amount: $amount,
            paymentMethod: $paymentMethod,
            metadata: ['test' => 'data']
        );

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('donation', $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertInstanceOf(Donation::class, $result['donation']);
        $this->assertInstanceOf(Payment::class, $result['payment']);

        // Assert donation was created with correct data
        $donation = $result['donation'];
        $this->assertEquals($campaign->id, $donation->campaign_id);
        $this->assertEquals($user->id, $donation->user_id);
        $this->assertEquals($amount, (float) $donation->amount);
        $this->assertEquals(DonationStatus::PENDING, $donation->status);

        // Assert payment was created with correct data
        $payment = $result['payment'];
        $this->assertEquals($donation->id, $payment->donation_id);
        $this->assertEquals($amount, (float) $payment->amount);
        $this->assertEquals('USD', $payment->currency);
        $this->assertEquals($paymentMethod, $payment->payment_method);
        $this->assertEquals(PaymentStatusEnum::PENDING, $payment->status);
        $this->assertNotNull($payment->initiated_at);
        $this->assertEquals(['test' => 'data'], $payment->metadata);
    }

    public function test_initialize_payment_stores_records_in_database(): void
    {
        // Arrange
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();
        $amount = 50.00;
        $paymentMethod = PaymentMethodEnum::FAKE;

        // Act
        $result = $this->service->initializePayment(
            campaignId: $campaign->id,
            userId: $user->id,
            amount: $amount,
            paymentMethod: $paymentMethod
        );

        // Assert - Check database records exist
        $this->assertDatabaseHas('donations', [
            'id' => $result['donation']->id,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => DonationStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $result['payment']->id,
            'donation_id' => $result['donation']->id,
            'amount' => $amount,
            'currency' => 'USD',
            'payment_method' => $paymentMethod->value,
            'status' => PaymentStatusEnum::PENDING->value,
        ]);
    }

    public function test_initialize_payment_with_invalid_amount_throws_exception(): void
    {
        // Arrange
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();
        $amount = 0.00; // Invalid amount
        $paymentMethod = PaymentMethodEnum::FAKE;

        // Assert & Act
        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $this->service->initializePayment(
            campaignId: $campaign->id,
            userId: $user->id,
            amount: $amount,
            paymentMethod: $paymentMethod
        );
    }

    public function test_initialize_payment_with_negative_amount_throws_exception(): void
    {
        // Arrange
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();
        $amount = -10.00; // Negative amount
        $paymentMethod = PaymentMethodEnum::FAKE;

        // Assert & Act
        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $this->service->initializePayment(
            campaignId: $campaign->id,
            userId: $user->id,
            amount: $amount,
            paymentMethod: $paymentMethod
        );
    }

    public function test_initialize_payment_with_invalid_campaign_throws_exception(): void
    {
        // Arrange
        $user = User::factory()->create();
        $invalidCampaignId = 'non-existent-id';
        $amount = 100.00;
        $paymentMethod = PaymentMethodEnum::FAKE;

        // Assert & Act
        $this->expectException(PaymentProcessingException::class);

        $this->service->initializePayment(
            campaignId: $invalidCampaignId,
            userId: $user->id,
            amount: $amount,
            paymentMethod: $paymentMethod
        );
    }

    public function test_initialize_payment_uses_transaction(): void
    {
        // Arrange
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();
        $amount = 100.00;
        $paymentMethod = PaymentMethodEnum::FAKE;

        // Mock DB to track transaction calls
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        // Act
        $result = $this->service->initializePayment(
            campaignId: $campaign->id,
            userId: $user->id,
            amount: $amount,
            paymentMethod: $paymentMethod
        );

        // Assert
        $this->assertNotNull($result);
    }

    public function test_initialize_payment_rolls_back_on_error(): void
    {
        // Arrange
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();
        $amount = 100.00;
        $paymentMethod = PaymentMethodEnum::FAKE;

        // Force an error by using invalid user ID
        $invalidUserId = 'invalid-user-id-that-will-fail';

        // Get initial counts
        $initialDonationCount = Donation::count();
        $initialPaymentCount = Payment::count();

        // Act & Assert
        try {
            $this->service->initializePayment(
                campaignId: $campaign->id,
                userId: $invalidUserId,
                amount: $amount,
                paymentMethod: $paymentMethod
            );
        } catch (PaymentProcessingException $e) {
            // Expected exception
        }

        // Assert - No records should be created due to rollback
        $this->assertEquals($initialDonationCount, Donation::count());
        $this->assertEquals($initialPaymentCount, Payment::count());
    }

    public function test_initialize_payment_with_metadata(): void
    {
        // Arrange
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();
        $amount = 100.00;
        $paymentMethod = PaymentMethodEnum::FAKE;
        $metadata = [
            'custom_field' => 'custom_value',
            'source' => 'web',
        ];

        // Act
        $result = $this->service->initializePayment(
            campaignId: $campaign->id,
            userId: $user->id,
            amount: $amount,
            paymentMethod: $paymentMethod,
            metadata: $metadata
        );

        // Assert
        $payment = $result['payment'];
        $this->assertEquals($metadata, $payment->metadata);
    }

    public function test_initialize_payment_without_metadata(): void
    {
        // Arrange
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();
        $amount = 100.00;
        $paymentMethod = PaymentMethodEnum::FAKE;

        // Act
        $result = $this->service->initializePayment(
            campaignId: $campaign->id,
            userId: $user->id,
            amount: $amount,
            paymentMethod: $paymentMethod
        );

        // Assert
        $payment = $result['payment'];
        $this->assertEquals([], $payment->metadata);
    }
}
