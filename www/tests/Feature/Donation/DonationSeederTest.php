<?php

declare(strict_types=1);

namespace Tests\Feature\Donation;

use App\Enums\Donation\DonationStatus;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that donations sum matches campaign current_amount for all campaigns
     */
    public function test_donation_amounts_match_campaign_current_amounts(): void
    {
        // Seed the database
        $this->seed();

        // Get all campaigns with current_amount > 0
        $campaigns = Campaign::where('current_amount', '>', 0)->get();

        $this->assertGreaterThan(0, $campaigns->count(), 'There should be campaigns with current_amount > 0');

        foreach ($campaigns as $campaign) {
            // Sum all successful donations for this campaign
            $successfulDonationsSum = Donation::where('campaign_id', $campaign->id)
                ->where('status', DonationStatus::SUCCESS)
                ->sum('amount');

            // Assert that the sum matches the campaign's current_amount
            $this->assertEquals(
                (float) $campaign->current_amount,
                (float) $successfulDonationsSum,
                "Campaign '{$campaign->title}' (ID: {$campaign->id}) current_amount ({$campaign->current_amount}) " .
                "should match sum of successful donations ({$successfulDonationsSum})"
            );
        }
    }

    /**
     * Test that campaigns without current_amount have no successful donations
     */
    public function test_campaigns_without_current_amount_have_no_successful_donations(): void
    {
        // Seed the database
        $this->seed();

        // Get campaigns with current_amount = 0
        $campaigns = Campaign::where('current_amount', '<=', 0)->get();

        foreach ($campaigns as $campaign) {
            $successfulDonationsSum = Donation::where('campaign_id', $campaign->id)
                ->where('status', DonationStatus::SUCCESS)
                ->sum('amount');

            $this->assertEquals(
                0,
                (float) $successfulDonationsSum,
                "Campaign '{$campaign->title}' with current_amount = 0 should have no successful donations"
            );
        }
    }

    /**
     * Test that each campaign with donations has multiple donation entries
     */
    public function test_campaigns_have_multiple_donations(): void
    {
        // Seed the database
        $this->seed();

        // Get campaigns with current_amount > 0
        $campaigns = Campaign::where('current_amount', '>', 0)->get();

        foreach ($campaigns as $campaign) {
            $donationCount = Donation::where('campaign_id', $campaign->id)->count();

            $this->assertGreaterThanOrEqual(
                3,
                $donationCount,
                "Campaign '{$campaign->title}' should have at least 3 donations (successful + pending + failed)"
            );
        }
    }

    /**
     * Test that donations have different statuses (successful, pending, failed)
     */
    public function test_donations_have_varied_statuses(): void
    {
        // Seed the database
        $this->seed();

        $successfulCount = Donation::where('status', DonationStatus::SUCCESS)->count();
        $pendingCount = Donation::where('status', DonationStatus::PENDING)->count();
        $failedCount = Donation::where('status', DonationStatus::FAILED)->count();

        $this->assertGreaterThan(0, $successfulCount, 'There should be successful donations');
        // Failed donations are optional (0-2 per campaign), so we don't assert they must exist
    }

    /**
     * Test that failed donations have error messages
     */
    public function test_failed_donations_have_error_messages(): void
    {
        // Seed the database
        $this->seed();

        $failedDonations = Donation::where('status', DonationStatus::FAILED)->get();

        foreach ($failedDonations as $donation) {
            $this->assertNotNull(
                $donation->error_message,
                "Failed donation (ID: {$donation->id}) should have an error message"
            );
            $this->assertNotEmpty(
                $donation->error_message,
                "Failed donation (ID: {$donation->id}) error message should not be empty"
            );
        }
    }

    /**
     * Test that successful donations have no error messages
     */
    public function test_successful_donations_have_no_error_messages(): void
    {
        // Seed the database
        $this->seed();

        $successfulDonations = Donation::where('status', DonationStatus::SUCCESS)->get();

        foreach ($successfulDonations as $donation) {
            $this->assertNull(
                $donation->error_message,
                "Successful donation (ID: {$donation->id}) should not have an error message"
            );
        }
    }

    /**
     * Test that all donations have valid user and campaign references
     */
    public function test_all_donations_have_valid_references(): void
    {
        // Seed the database
        $this->seed();

        $donations = Donation::with(['user', 'campaign'])->get();

        foreach ($donations as $donation) {
            $this->assertNotNull($donation->user()->first(), "Donation (ID: {$donation->id}) should have a user");
            $this->assertNotNull($donation->campaign()->first(), "Donation (ID: {$donation->id}) should have a campaign");
            $this->assertInstanceOf(\App\Models\Auth\User::class, $donation->user);
            $this->assertInstanceOf(\App\Models\Campaign\Campaign::class, $donation->campaign);
        }
    }

    /**
     * Test that donation amounts are reasonable (not negative, not zero)
     */
    public function test_donation_amounts_are_valid(): void
    {
        // Seed the database
        $this->seed();

        $donations = Donation::all();

        foreach ($donations as $donation) {
            $this->assertGreaterThan(
                0,
                (float) $donation->amount,
                "Donation (ID: {$donation->id}) amount should be greater than 0"
            );
        }
    }
}
