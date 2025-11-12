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
 * Feature tests for PaymentCallbackController
 *
 * These tests verify that the controller properly enforces authorization
 * and prevents unauthorized access and duplicate payment processing.
 */
class PaymentCallbackControllerTest extends TestCase
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

    public function test_callback_processes_successfully_for_pending_payment(): void
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

        // Act - Simulate a successful callback from fake payment gateway
        $response = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => $payment->id]), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Assert - Should redirect to payment result page
        $response->assertRedirect(route('payment.result', ['payment' => $payment->id]));
        $response->assertSessionHas('success');
    }

    public function test_callback_requires_authentication(): void
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

        // Act - Without authentication
        $response = $this->post(route('payment.callback', ['payment' => $payment->id]), [
            'status' => 'success',
            'transaction_id' => 'fake_txn_' . uniqid(),
        ]);

        // Assert - Should redirect to login
        $response->assertStatus(302);
        $response->assertRedirect(route('login.form'));
    }

    public function test_callback_denies_access_to_other_users_payment(): void
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

        // Act - Current user trying to process another user's payment
        $response = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => $payment->id]), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Assert - Should return 403 Forbidden
        $response->assertStatus(403);
    }

    public function test_callback_prevents_double_processing_of_completed_payment(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        // Create a payment that's already completed
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::COMPLETED,
            'payment_method' => PaymentMethodEnum::FAKE,
            'completed_at' => now(),
        ]);

        // Act - Try to process the callback again
        $response = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => $payment->id]), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Assert - Should return 403 Forbidden (policy denies)
        $response->assertStatus(403);
    }

    public function test_callback_prevents_reprocessing_of_failed_payment(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        // Create a payment that already failed
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::FAILED,
            'payment_method' => PaymentMethodEnum::FAKE,
            'failed_at' => now(),
        ]);

        // Act - Try to process the callback again
        $response = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => $payment->id]), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Assert - Should return 403 Forbidden (policy denies)
        $response->assertStatus(403);
    }

    public function test_callback_denies_processing_payment(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        // Create a payment that's currently processing
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PROCESSING,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act - Try to process the callback while payment is processing
        $response = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => $payment->id]), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Assert - Should return 403 Forbidden (policy denies)
        $response->assertStatus(403);
    }

    public function test_callback_denies_refunded_payment(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        // Create a payment that's refunded
        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::REFUNDED,
            'payment_method' => PaymentMethodEnum::FAKE,
            'refunded_at' => now(),
        ]);

        // Act - Try to process the callback for refunded payment
        $response = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => $payment->id]), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Assert - Should return 403 Forbidden (policy denies)
        $response->assertStatus(403);
    }

    public function test_callback_works_with_different_payment_methods(): void
    {
        // Test with PayPal
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $paypalPayment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PENDING,
            'payment_method' => PaymentMethodEnum::PAYPAL,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => $paypalPayment->id]), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Should be allowed (even though it's not Fake payment method)
        $response->assertRedirect();

        // Test with Credit Card
        $creditCardPayment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PENDING,
            'payment_method' => PaymentMethodEnum::CREDIT_CARD,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => $creditCardPayment->id]), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Should be allowed
        $response->assertRedirect();
    }

    public function test_callback_accepts_get_requests(): void
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

        // Act - Some payment gateways use GET for callbacks
        $response = $this->actingAs($this->user)
            ->get(route('payment.callback', ['payment' => $payment->id]) . '?status=success&transaction_id=fake_txn_' . uniqid());

        // Assert - Should redirect
        $response->assertRedirect();
    }

    public function test_callback_returns_404_for_nonexistent_payment(): void
    {
        // Act - Try to process callback for non-existent payment
        $response = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => '99999999-9999-9999-9999-999999999999']), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Assert - Should return 404
        $response->assertStatus(404);
    }

    public function test_callback_policy_prevents_multiple_callback_triggers(): void
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

        // Act - First callback (should succeed)
        $firstResponse = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => $payment->id]), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Assert first callback succeeded
        $firstResponse->assertRedirect();

        // Refresh the payment from database to get updated status
        $payment->refresh();

        // Assert payment is now completed
        $this->assertEquals(PaymentStatusEnum::COMPLETED, $payment->status);

        // Act - Second callback (should be denied by policy)
        $secondResponse = $this->actingAs($this->user)
            ->post(route('payment.callback', ['payment' => $payment->id]), [
                'status' => 'success',
                'transaction_id' => 'fake_txn_' . uniqid(),
            ]);

        // Assert - Second callback should be forbidden
        $secondResponse->assertStatus(403);
    }
}
