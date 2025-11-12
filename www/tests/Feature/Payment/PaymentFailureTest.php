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

class PaymentFailureTest extends TestCase
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
            'status' => DonationStatus::FAILED,
        ]);

        // Create failed payment
        $this->payment = Payment::factory()->create([
            'donation_id' => $this->donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::FAILED,
            'amount' => 50.00,
            'currency' => 'USD',
            'error_message' => 'Insufficient funds',
            'error_code' => 'INSUFFICIENT_FUNDS',
            'failed_at' => now(),
        ]);
    }

    /** @test */
    public function it_displays_payment_failure_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.failure', ['payment' => $this->payment->id]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.failure');
    }

    /** @test */
    public function it_passes_correct_data_to_failure_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.failure', ['payment' => $this->payment->id]));

        $response->assertViewHas('payment', function (Payment $payment) {
            return $payment->id === $this->payment->id
                && $payment->amount == 50.00
                && $payment->currency === 'USD'
                && $payment->error_message === 'Insufficient funds'
                && $payment->error_code === 'INSUFFICIENT_FUNDS';
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
    public function it_requires_authentication_to_view_failure_page(): void
    {
        $response = $this->get(route('payment.failure', ['payment' => $this->payment->id]));

        $response->assertRedirect(route('login.form'));
    }

    /** @test */
    public function it_handles_payment_not_found(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.failure', ['payment' => 'non-existent-id']));

        $response->assertStatus(404);
    }

    /** @test */
    public function failure_page_loads_all_required_relationships(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment.failure', ['payment' => $this->payment->id]));

        $response->assertStatus(200);

        // Verify relationships are loaded
        $payment = $response->viewData('payment');
        $this->assertTrue($payment->relationLoaded('donation'));
        $this->assertTrue($payment->donation->relationLoaded('campaign'));
        $this->assertTrue($payment->donation->relationLoaded('user'));
    }

    /** @test */
    public function callback_handler_returns_correct_redirect_route_for_failed_payment(): void
    {
        // This test verifies that the FakePaymentCallbackHandler returns the correct failure route

        $handler = new \App\Services\Payment\CallbackHandlers\FakePaymentCallbackHandler();

        $payment = Payment::factory()->create([
            'donation_id' => $this->donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $request = new \Illuminate\Http\Request([
            'status' => 'failed',
            'error_message' => 'Card declined',
            'error_code' => 'CARD_DECLINED',
        ]);

        $result = $handler->handleCallback($payment, $request);

        $this->assertEquals('payment.failure', $result->redirectRoute);
        $this->assertEquals($payment->id, $result->redirectParams['payment']);
        $this->assertEquals('Card declined', $result->errorMessage);
        $this->assertEquals('CARD_DECLINED', $result->errorCode);
    }
}
