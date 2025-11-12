<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use App\Policies\Payment\FakePaymentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakePaymentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentPolicy $policy;
    private User $user;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new FakePaymentPolicy();
        $this->user = User::factory()->create();
        $this->campaign = Campaign::factory()->create();
    }

    /**
     * @test
     */
    public function it_allows_access_when_all_conditions_are_met(): void
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
    public function it_denies_access_when_payment_does_not_belong_to_user(): void
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
    public function it_denies_access_when_payment_status_is_not_pending(): void
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
    public function it_denies_access_when_payment_method_is_not_fake(): void
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
    public function it_denies_access_when_payment_is_failed(): void
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
    public function it_denies_access_when_payment_is_processing(): void
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
}
