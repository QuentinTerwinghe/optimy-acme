<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for FakePaymentController
 */
class FakePaymentControllerTest extends TestCase
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

    public function test_show_fake_payment_page_successfully(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PENDING,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('payment.fake.show', ['payment' => $payment->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('payment.fake');
        $response->assertViewHas('payment');
        $response->assertViewHas('failureReasons');

        // Verify the payment data passed to the view
        $viewPayment = $response->viewData('payment');
        $this->assertEquals($payment->id, $viewPayment->id);
    }

    public function test_show_fake_payment_page_requires_authentication(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PENDING,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act - Without authentication
        $response = $this->get(route('payment.fake.show', ['payment' => $payment->id]));

        // Assert
        $response->assertStatus(302);
        $response->assertRedirect(route('login.form'));
    }

    public function test_show_fake_payment_page_denies_access_to_other_users_payment(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $donation = Donation::factory()->create([
            'user_id' => $otherUser->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PENDING,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('payment.fake.show', ['payment' => $payment->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_show_fake_payment_page_denies_access_when_payment_is_not_pending(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::COMPLETED,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('payment.fake.show', ['payment' => $payment->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_show_fake_payment_page_denies_access_when_payment_method_is_not_fake(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PENDING,
            'payment_method' => PaymentMethodEnum::PAYPAL,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('payment.fake.show', ['payment' => $payment->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_show_fake_payment_page_returns_failure_reasons(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PENDING,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('payment.fake.show', ['payment' => $payment->id]));

        // Assert
        $response->assertStatus(200);
        $failureReasons = $response->viewData('failureReasons');

        $this->assertIsArray($failureReasons);
        $this->assertNotEmpty($failureReasons);
        $this->assertArrayHasKey('value', $failureReasons[0]);
        $this->assertArrayHasKey('label', $failureReasons[0]);
    }

    public function test_show_fake_payment_page_with_invalid_payment_id(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->get(route('payment.fake.show', ['payment' => 'invalid-payment-id']));

        // Assert
        $response->assertStatus(404);
    }
}
