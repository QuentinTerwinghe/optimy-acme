<?php

namespace Tests\Feature\Payment;

use App\Enums\Donation\DonationStatus;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentResultTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Campaign $campaign;
    private Donation $successDonation;
    private Donation $failedDonation;
    private Payment $successfulPayment;
    private Payment $failedPayment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test Donor',
            'email' => 'donor@test.com',
        ]);

        // Create test campaign
        $this->campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'description' => 'Test campaign description',
            'goal_amount' => 1000,
        ]);

        // Create successful donation
        $this->successDonation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Create completed payment
        $this->successfulPayment = Payment::factory()->create([
            'donation_id' => $this->successDonation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::COMPLETED,
            'amount' => 50.00,
            'currency' => 'USD',
            'transaction_id' => 'TEST_TXN_123',
            'completed_at' => now(),
        ]);

        // Create failed donation
        $this->failedDonation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 75.00,
            'status' => DonationStatus::FAILED,
        ]);

        // Create failed payment
        $this->failedPayment = Payment::factory()->create([
            'donation_id' => $this->failedDonation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::FAILED,
            'amount' => 75.00,
            'currency' => 'USD',
            'error_message' => 'Insufficient funds',
            'error_code' => 'INSUFFICIENT_FUNDS',
            'failed_at' => now(),
        ]);
    }

    #[Test]
    public function it_displays_success_view_for_completed_payment(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $this->successfulPayment->id]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.success');
    }

    #[Test]
    public function it_displays_failure_view_for_failed_payment(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $this->failedPayment->id]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.failure');
    }

    #[Test]
    public function it_passes_correct_data_to_success_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $this->successfulPayment->id]));

        $response->assertViewHas('payment', function (Payment $payment) {
            return $payment->id === $this->successfulPayment->id
                && $payment->amount == 50.00
                && $payment->currency === 'USD'
                && $payment->transaction_id === 'TEST_TXN_123';
        });

        $response->assertViewHas('donation', function (Donation $donation) {
            return $donation->id === $this->successDonation->id
                && $donation->amount == 50.00;
        });

        $response->assertViewHas('campaign', function (Campaign $campaign) {
            return $campaign->id === $this->campaign->id
                && $campaign->title === 'Test Campaign';
        });

        $response->assertViewHas('user', function (User $user) {
            return $user->id === $this->user->id
                && $user->name === 'Test Donor';
        });
    }

    #[Test]
    public function it_passes_correct_data_to_failure_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $this->failedPayment->id]));

        $response->assertViewHas('payment', function (Payment $payment) {
            return $payment->id === $this->failedPayment->id
                && $payment->amount == 75.00
                && $payment->currency === 'USD'
                && $payment->error_message === 'Insufficient funds'
                && $payment->error_code === 'INSUFFICIENT_FUNDS';
        });

        $response->assertViewHas('donation', function (Donation $donation) {
            return $donation->id === $this->failedDonation->id
                && $donation->amount == 75.00;
        });

        $response->assertViewHas('campaign', function (Campaign $campaign) {
            return $campaign->id === $this->campaign->id
                && $campaign->title === 'Test Campaign';
        });

        $response->assertViewHas('user', function (User $user) {
            return $user->id === $this->user->id
                && $user->name === 'Test Donor';
        });
    }

    #[Test]
    public function it_requires_authentication_to_view_result_page(): void
    {
        $response = $this->get(route('payment.result', ['payment' => $this->successfulPayment->id]));

        $response->assertRedirect(route('login.form'));
    }

    #[Test]
    public function it_handles_payment_not_found(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => 'non-existent-id']));

        $response->assertStatus(404);
    }

    #[Test]
    public function result_page_loads_all_required_relationships(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $this->successfulPayment->id]));

        $response->assertStatus(200);

        // Verify relationships are loaded
        $payment = $response->viewData('payment');
        $this->assertTrue($payment->relationLoaded('donation'));
        $this->assertTrue($payment->donation->relationLoaded('campaign'));
        $this->assertTrue($payment->donation->relationLoaded('user'));
    }

    #[Test]
    public function callback_redirects_to_result_page_on_successful_payment(): void
    {
        // Create a new pending donation and payment for this test
        $newDonation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 100.00,
            'status' => DonationStatus::PENDING,
        ]);

        $pendingPayment = Payment::factory()->create([
            'donation_id' => $newDonation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
            'amount' => 100.00,
            'currency' => 'USD',
            'payload' => ['session_id' => 'test_session_123'],
        ]);

        // Simulate callback from fake payment gateway with success status
        $response = $this->actingAs($this->user)->get(
            route('payment.callback', [
                'payment' => $pendingPayment->id,
                'status' => 'success',
                'transaction_id' => 'FAKE_TXN_456',
                'session_id' => 'test_session_123',
            ])
        );

        // Assert redirected to result page
        $response->assertRedirect(route('payment.result', ['payment' => $pendingPayment->id]));
        $response->assertSessionHas('success', 'Payment completed successfully!');
    }

    #[Test]
    public function callback_redirects_to_result_page_on_failed_payment(): void
    {
        // Create a new pending donation and payment for this test
        $newDonation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 120.00,
            'status' => DonationStatus::PENDING,
        ]);

        $pendingPayment = Payment::factory()->create([
            'donation_id' => $newDonation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
            'amount' => 120.00,
            'currency' => 'USD',
            'payload' => ['session_id' => 'test_session_456'],
        ]);

        // Simulate callback from fake payment gateway with failed status
        $response = $this->actingAs($this->user)->get(
            route('payment.callback', [
                'payment' => $pendingPayment->id,
                'status' => 'failed',
                'error_message' => 'Card declined',
                'error_code' => 'CARD_DECLINED',
                'session_id' => 'test_session_456',
            ])
        );

        // Assert redirected to result page
        $response->assertRedirect(route('payment.result', ['payment' => $pendingPayment->id]));
        $response->assertSessionHas('error', 'Card declined');
    }

    #[Test]
    public function it_prevents_url_manipulation_by_checking_actual_payment_status(): void
    {
        // This is the key security test: even though a user might try to access
        // a failed payment with expectation of seeing success, the controller
        // will show the correct view based on the actual payment status

        // Try to access failed payment (should show failure view, not success)
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $this->failedPayment->id]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.failure');  // Not success!

        // Try to access successful payment (should show success view)
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $this->successfulPayment->id]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.success');  // Not failure!
    }

    #[Test]
    public function callback_handler_returns_correct_redirect_route(): void
    {
        // This test verifies that the FakePaymentCallbackHandler returns the correct result route

        $handler = new \App\Services\Payment\CallbackHandlers\FakePaymentCallbackHandler();

        $payment = Payment::factory()->create([
            'donation_id' => $this->successDonation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $successRequest = new \Illuminate\Http\Request([
            'status' => 'success',
            'transaction_id' => 'TXN_789',
        ]);

        $result = $handler->handleCallback($payment, $successRequest);

        $this->assertEquals('payment.result', $result->redirectRoute);
        $this->assertEquals($payment->id, $result->redirectParams['payment']);

        // Test failure case
        $failureRequest = new \Illuminate\Http\Request([
            'status' => 'failed',
            'error_message' => 'Card declined',
            'error_code' => 'CARD_DECLINED',
        ]);

        $result = $handler->handleCallback($payment, $failureRequest);

        $this->assertEquals('payment.result', $result->redirectRoute);
        $this->assertEquals($payment->id, $result->redirectParams['payment']);
    }

    // ============================================================
    // AUTHORIZATION TESTS
    // ============================================================

    #[Test]
    public function it_denies_access_to_payment_result_belonging_to_different_user(): void
    {
        // Create another user
        $otherUser = User::factory()->create([
            'name' => 'Other User',
            'email' => 'other@test.com',
        ]);

        // Try to access the payment that belongs to $this->user
        $response = $this->actingAs($otherUser)->get(route('payment.result', ['payment' => $this->successfulPayment->id]));

        // Should be denied with 403 Forbidden
        $response->assertStatus(403);
    }

    #[Test]
    public function it_denies_access_to_payment_with_pending_status(): void
    {
        // Create a pending payment
        $pendingDonation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 30.00,
            'status' => DonationStatus::PENDING,
        ]);

        $pendingPayment = Payment::factory()->create([
            'donation_id' => $pendingDonation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
            'amount' => 30.00,
            'currency' => 'USD',
        ]);

        // Try to access result page for pending payment
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $pendingPayment->id]));

        // Should be denied with 403 Forbidden
        $response->assertStatus(403);
    }

    #[Test]
    public function it_denies_access_to_payment_with_processing_status(): void
    {
        // Create a processing payment
        $processingDonation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 40.00,
            'status' => DonationStatus::PENDING,
        ]);

        $processingPayment = Payment::factory()->create([
            'donation_id' => $processingDonation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PROCESSING,
            'amount' => 40.00,
            'currency' => 'USD',
        ]);

        // Try to access result page for processing payment
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $processingPayment->id]));

        // Should be denied with 403 Forbidden
        $response->assertStatus(403);
    }

    #[Test]
    public function it_denies_access_to_payment_with_refunded_status(): void
    {
        // Create a refunded payment
        $refundedDonation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 60.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        $refundedPayment = Payment::factory()->create([
            'donation_id' => $refundedDonation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::REFUNDED,
            'amount' => 60.00,
            'currency' => 'USD',
            'refunded_at' => now(),
        ]);

        // Try to access result page for refunded payment
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $refundedPayment->id]));

        // Should be denied with 403 Forbidden
        $response->assertStatus(403);
    }

    #[Test]
    public function it_allows_owner_to_view_their_own_completed_payment(): void
    {
        // The user should be able to view their own completed payment
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $this->successfulPayment->id]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.success');
    }

    #[Test]
    public function it_allows_owner_to_view_their_own_failed_payment(): void
    {
        // The user should be able to view their own failed payment
        $response = $this->actingAs($this->user)->get(route('payment.result', ['payment' => $this->failedPayment->id]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.failure');
    }
}
