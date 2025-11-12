<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\Donation\DonationStatus;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Jobs\Campaign\UpdateCampaignAmountJob;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature tests for campaign amount updates when payments succeed
 */
class CampaignAmountUpdateOnPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->campaign = Campaign::factory()->create([
            'current_amount' => 0,
        ]);
    }

    public function test_campaign_amount_is_updated_after_successful_payment(): void
    {
        // Create donation with successful status
        $donation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 75.50,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Process the job synchronously
        UpdateCampaignAmountJob::dispatchSync($this->campaign->id);

        // Assert campaign amount was updated
        $this->campaign->refresh();
        $this->assertEquals('75.50', $this->campaign->current_amount);
    }

    public function test_campaign_amount_reflects_multiple_successful_donations(): void
    {
        // Create first successful donation
        $donation1 = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Update campaign amount for first donation
        UpdateCampaignAmountJob::dispatchSync($this->campaign->id);
        $this->campaign->refresh();
        $this->assertEquals('50.00', $this->campaign->current_amount);

        // Create second donation and payment
        $donation2 = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 30.00,
            'status' => DonationStatus::PENDING,
        ]);

        $payment2 = Payment::factory()->create([
            'donation_id' => $donation2->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
            'amount' => 30.00,
        ]);

        // Simulate second payment succeeding by marking donation as success
        $donation2->update(['status' => DonationStatus::SUCCESS]);

        // Process the job
        UpdateCampaignAmountJob::dispatchSync($this->campaign->id);

        // Assert campaign amount includes both donations
        $this->campaign->refresh();
        $this->assertEquals('80.00', $this->campaign->current_amount);
    }

    public function test_campaign_amount_excludes_pending_donations(): void
    {
        // Create successful donation
        Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Create pending donation (should not be counted)
        Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 100.00,
            'status' => DonationStatus::PENDING,
        ]);

        // Update campaign amount
        UpdateCampaignAmountJob::dispatchSync($this->campaign->id);

        // Assert only successful donation is counted
        $this->campaign->refresh();
        $this->assertEquals('50.00', $this->campaign->current_amount);
    }

    public function test_campaign_amount_excludes_failed_donations(): void
    {
        // Create successful donation
        Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Create failed donation (should not be counted)
        Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 200.00,
            'status' => DonationStatus::FAILED,
        ]);

        // Update campaign amount
        UpdateCampaignAmountJob::dispatchSync($this->campaign->id);

        // Assert only successful donation is counted
        $this->campaign->refresh();
        $this->assertEquals('50.00', $this->campaign->current_amount);
    }

    public function test_campaign_amount_is_not_updated_when_payment_fails(): void
    {
        Queue::fake();

        // Create donation and payment
        $donation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 50.00,
            'status' => DonationStatus::PENDING,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
            'amount' => 50.00,
        ]);

        // Simulate failed payment callback using correct route
        $this->post("/payment/callback/{$payment->id}", [
            'status' => 'failed',
            'error_message' => 'Payment declined',
        ]);

        // Assert job was NOT dispatched for failed payment
        Queue::assertNotPushed(UpdateCampaignAmountJob::class);

        // Assert campaign amount remains unchanged
        $this->campaign->refresh();
        $this->assertEquals('0.00', $this->campaign->current_amount);
    }

    public function test_campaign_amount_update_is_idempotent(): void
    {
        // Create successful donation
        Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Dispatch job multiple times
        UpdateCampaignAmountJob::dispatchSync($this->campaign->id);
        UpdateCampaignAmountJob::dispatchSync($this->campaign->id);
        UpdateCampaignAmountJob::dispatchSync($this->campaign->id);

        // Assert amount is correct and not multiplied
        $this->campaign->refresh();
        $this->assertEquals('50.00', $this->campaign->current_amount);
    }

    public function test_multiple_campaigns_are_updated_independently(): void
    {
        $campaign2 = Campaign::factory()->create(['current_amount' => 0]);

        // Create donations for different campaigns
        Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'amount' => 50.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        Donation::factory()->create([
            'campaign_id' => $campaign2->id,
            'amount' => 100.00,
            'status' => DonationStatus::SUCCESS,
        ]);

        // Update only campaign1
        UpdateCampaignAmountJob::dispatchSync($this->campaign->id);

        // Assert only campaign1 is updated
        $this->campaign->refresh();
        $campaign2->refresh();

        $this->assertEquals('50.00', $this->campaign->current_amount);
        $this->assertEquals('0.00', $campaign2->current_amount);

        // Now update campaign2
        UpdateCampaignAmountJob::dispatchSync($campaign2->id);
        $campaign2->refresh();

        $this->assertEquals('100.00', $campaign2->current_amount);
    }

    public function test_full_payment_flow_updates_campaign_correctly(): void
    {
        // Initial state: campaign has no donations
        $this->assertEquals('0.00', $this->campaign->current_amount);

        // User initiates payment
        $donation = Donation::factory()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id,
            'amount' => 125.75,
            'status' => DonationStatus::PENDING,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
            'amount' => 125.75,
        ]);

        // Campaign amount should still be 0
        $this->campaign->refresh();
        $this->assertEquals('0.00', $this->campaign->current_amount);

        // Payment succeeds - mark donation as successful
        $donation->update(['status' => DonationStatus::SUCCESS]);

        // Verify donation is marked as successful
        $donation->refresh();
        $this->assertEquals(DonationStatus::SUCCESS, $donation->status);

        // Process the update job
        UpdateCampaignAmountJob::dispatchSync($this->campaign->id);

        // Campaign amount should now reflect the successful donation
        $this->campaign->refresh();
        $this->assertEquals('125.75', $this->campaign->current_amount);
    }
}
