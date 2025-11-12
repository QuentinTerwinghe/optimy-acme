<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use App\Policies\Payment\PaymentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PaymentPolicy $policy;
    private User $user;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PaymentPolicy();
        $this->user = User::factory()->create();
        $this->campaign = Campaign::factory()->create();
    }

    // ============================================================
    // ACCESS METHOD TESTS (for fake payment page)
    // ============================================================

    /**
     * @test
     */
    public function access_allows_when_all_conditions_are_met(): void
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
        $result = $this->policy->access($this->user, $payment);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function access_denies_when_payment_does_not_belong_to_user(): void
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
        $result = $this->policy->access($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function access_denies_when_payment_status_is_not_pending(): void
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
        $result = $this->policy->access($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function access_denies_when_payment_method_is_not_fake(): void
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
        $result = $this->policy->access($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function access_denies_when_payment_is_failed(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::FAILED,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $result = $this->policy->access($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function access_denies_when_payment_is_processing(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PROCESSING,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $result = $this->policy->access($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    // ============================================================
    // VIEW METHOD TESTS (for payment result page)
    // ============================================================

    /**
     * @test
     */
    public function view_allows_when_payment_is_completed_and_belongs_to_user(): void
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
        $result = $this->policy->view($this->user, $payment);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function view_allows_when_payment_is_failed_and_belongs_to_user(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::FAILED,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $result = $this->policy->view($this->user, $payment);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function view_denies_when_payment_does_not_belong_to_user(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $donation = Donation::factory()->create([
            'user_id' => $otherUser->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::COMPLETED,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $result = $this->policy->view($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function view_denies_when_payment_status_is_pending(): void
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
        $result = $this->policy->view($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function view_denies_when_payment_status_is_processing(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PROCESSING,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $result = $this->policy->view($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function view_denies_when_payment_status_is_refunded(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::REFUNDED,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $result = $this->policy->view($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function view_denies_when_payment_has_no_donation(): void
    {
        // Arrange - Create a payment with a donation first
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::COMPLETED,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Manually unset the donation relationship to simulate a missing donation
        // In real world this shouldn't happen due to foreign key constraint
        $payment->setRelation('donation', null);

        // Act
        $result = $this->policy->view($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function view_denies_when_different_user_tries_to_access_completed_payment(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
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
        $result = $this->policy->view($otherUser, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function view_denies_when_different_user_tries_to_access_failed_payment(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::FAILED,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $result = $this->policy->view($otherUser, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function view_works_with_different_payment_methods(): void
    {
        // Test with PayPal
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $paypalPayment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::COMPLETED,
            'payment_method' => PaymentMethodEnum::PAYPAL,
        ]);

        $this->assertTrue($this->policy->view($this->user, $paypalPayment));

        // Test with Credit Card
        $creditCardPayment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::COMPLETED,
            'payment_method' => PaymentMethodEnum::CREDIT_CARD,
        ]);

        $this->assertTrue($this->policy->view($this->user, $creditCardPayment));
    }

    /**
     * @test
     */
    public function access_denies_when_payment_has_no_donation(): void
    {
        // Arrange - Create a payment with a donation first
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PENDING,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Manually unset the donation relationship to simulate a missing donation
        // In real world this shouldn't happen due to foreign key constraint
        $payment->setRelation('donation', null);

        // Act
        $result = $this->policy->access($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    // ============================================================
    // PROCESS CALLBACK METHOD TESTS (for payment callback processing)
    // ============================================================

    /**
     * @test
     */
    public function processCallback_allows_when_payment_is_pending_and_belongs_to_user(): void
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
        $result = $this->policy->processCallback($this->user, $payment);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function processCallback_allows_pending_payment_with_different_payment_methods(): void
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

        $this->assertTrue($this->policy->processCallback($this->user, $paypalPayment));

        // Test with Credit Card
        $creditCardPayment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PENDING,
            'payment_method' => PaymentMethodEnum::CREDIT_CARD,
        ]);

        $this->assertTrue($this->policy->processCallback($this->user, $creditCardPayment));
    }

    /**
     * @test
     */
    public function processCallback_denies_when_payment_does_not_belong_to_user(): void
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
        $result = $this->policy->processCallback($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function processCallback_denies_when_payment_status_is_completed(): void
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
        $result = $this->policy->processCallback($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function processCallback_denies_when_payment_status_is_failed(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::FAILED,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $result = $this->policy->processCallback($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function processCallback_denies_when_payment_status_is_processing(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PROCESSING,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $result = $this->policy->processCallback($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function processCallback_denies_when_payment_status_is_refunded(): void
    {
        // Arrange
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::REFUNDED,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Act
        $result = $this->policy->processCallback($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function processCallback_denies_when_payment_has_no_donation(): void
    {
        // Arrange - Create a payment with a donation first
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::PENDING,
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        // Manually unset the donation relationship to simulate a missing donation
        // In real world this shouldn't happen due to foreign key constraint
        $payment->setRelation('donation', null);

        // Act
        $result = $this->policy->processCallback($this->user, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function processCallback_denies_when_different_user_tries_to_process_pending_payment(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
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
        $result = $this->policy->processCallback($otherUser, $payment);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function processCallback_prevents_double_processing_of_completed_payment(): void
    {
        // Arrange - Simulate a completed payment being triggered again
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::COMPLETED,
            'payment_method' => PaymentMethodEnum::PAYPAL,
            'completed_at' => now(),
        ]);

        // Act
        $result = $this->policy->processCallback($this->user, $payment);

        // Assert
        $this->assertFalse($result, 'Should prevent double processing of completed payment');
    }

    /**
     * @test
     */
    public function processCallback_prevents_reprocessing_of_failed_payment(): void
    {
        // Arrange - Simulate a failed payment being triggered again
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'status' => PaymentStatusEnum::FAILED,
            'payment_method' => PaymentMethodEnum::FAKE,
            'failed_at' => now(),
        ]);

        // Act
        $result = $this->policy->processCallback($this->user, $payment);

        // Assert
        $this->assertFalse($result, 'Should prevent reprocessing of failed payment');
    }
}
