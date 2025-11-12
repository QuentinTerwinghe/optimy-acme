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
use Tests\TestCase;

class PaymentSuccessTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Campaign $campaign;
    private Donation $donation;
    private Payment $payment;

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

        // Create test donation
        $this->donation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Create completed payment
        $this->payment = Payment::factory()->create([
            'donation_id' => $this->donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::COMPLETED,
            'amount' => 50.00,
            'currency' => 'USD',
            'transaction_id' => 'TEST_TXN_123',
            'completed_at' => now(),
        ]);
    }

    /** @test */
    public function it_displays_payment_success_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.success', ['payment' => $this->payment->id]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.success');
    }

    /** @test */
    public function it_passes_correct_data_to_success_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.success', ['payment' => $this->payment->id]));

        $response->assertViewHas('payment', function (Payment $payment) {
            return $payment->id === $this->payment->id
                && $payment->amount == 50.00
                && $payment->currency === 'USD'
                && $payment->transaction_id === 'TEST_TXN_123';
        });

        $response->assertViewHas('donation', function (Donation $donation) {
            return $donation->id === $this->donation->id
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

    /** @test */
    public function it_requires_authentication_to_view_success_page(): void
    {
        $response = $this->get(route('payment.success', ['payment' => $this->payment->id]));

        $response->assertRedirect(route('login.form'));
    }

    /** @test */
    public function callback_redirects_to_success_page_on_successful_payment(): void
    {
        // Create a new pending donation and payment for this test
        $newDonation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 75.00,
            'status' => DonationStatus::PENDING,
        ]);

        $pendingPayment = Payment::factory()->create([
            'donation_id' => $newDonation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
            'amount' => 75.00,
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

        // Assert redirected to success page
        $response->assertRedirect(route('payment.success', ['payment' => $pendingPayment->id]));
        $response->assertSessionHas('success', 'Payment completed successfully!');
    }

    /** @test */
    public function it_handles_payment_not_found(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.success', ['payment' => 'non-existent-id']));

        $response->assertStatus(404);
    }

    /** @test */
    public function success_page_loads_all_required_relationships(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.success', ['payment' => $this->payment->id]));

        $response->assertStatus(200);

        // Verify relationships are loaded
        $payment = $response->viewData('payment');
        $this->assertTrue($payment->relationLoaded('donation'));
        $this->assertTrue($payment->donation->relationLoaded('campaign'));
        $this->assertTrue($payment->donation->relationLoaded('user'));
    }
}
