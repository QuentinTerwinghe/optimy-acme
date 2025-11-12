<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Donation\DonationStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use Illuminate\Database\Seeder;

class DonationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates donations for each campaign, ensuring that:
     * 1. The sum of successful donations equals the campaign's current_amount
     * 2. Multiple donations are created to simulate realistic donation patterns
     * 3. A mix of successful, pending, and failed donations are included
     */
    public function run(): void
    {
        // Get all campaigns
        $campaigns = Campaign::all();

        // Get all users for random assignment
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        foreach ($campaigns as $campaign) {
            $currentAmount = (float) $campaign->current_amount;

            // Skip campaigns with no donations (current_amount = 0)
            if ($currentAmount <= 0) {
                continue;
            }

            // Determine number of donations for this campaign (between 3 and 15)
            $numberOfDonations = fake()->numberBetween(3, 15);

            // Split the current_amount into multiple donation amounts
            $donationAmounts = $this->splitAmount($currentAmount, $numberOfDonations);

            // Create successful donations that sum to current_amount
            foreach ($donationAmounts as $amount) {
                Donation::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $users->random()->id,
                    'amount' => $amount,
                    'status' => DonationStatus::SUCCESS,
                    'error_message' => null,
                    'created_by' => $users->random()->id,
                    'updated_by' => $users->random()->id,
                    'created_at' => $this->randomDateBetween($campaign->start_date, now()),
                ]);
            }

            // Add some pending donations (1-3 per campaign)
            $pendingCount = fake()->numberBetween(1, 3);
            for ($i = 0; $i < $pendingCount; $i++) {
                Donation::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $users->random()->id,
                    'amount' => fake()->randomFloat(2, 10, 500),
                    'status' => DonationStatus::PENDING,
                    'error_message' => null,
                    'created_by' => $users->random()->id,
                    'updated_by' => $users->random()->id,
                    'created_at' => $this->randomDateBetween($campaign->start_date, now()),
                ]);
            }

            // Add some failed donations (0-2 per campaign)
            $failedCount = fake()->numberBetween(0, 2);
            for ($i = 0; $i < $failedCount; $i++) {
                Donation::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $users->random()->id,
                    'amount' => fake()->randomFloat(2, 10, 1000),
                    'status' => DonationStatus::FAILED,
                    'error_message' => fake()->randomElement([
                        'Payment gateway timeout',
                        'Insufficient funds',
                        'Card declined',
                        'Payment processor error',
                        'Invalid card details',
                        'Transaction limit exceeded',
                    ]),
                    'created_by' => $users->random()->id,
                    'updated_by' => $users->random()->id,
                    'created_at' => $this->randomDateBetween($campaign->start_date, now()),
                ]);
            }
        }

        $this->command->info('Donations seeded successfully!');
    }

    /**
     * Split an amount into multiple random donation amounts
     *
     * @param float $totalAmount The total amount to split
     * @param int $numberOfDonations The number of donations to create
     * @return array<float> Array of donation amounts that sum to totalAmount
     */
    private function splitAmount(float $totalAmount, int $numberOfDonations): array
    {
        if ($numberOfDonations <= 0) {
            return [];
        }

        if ($numberOfDonations === 1) {
            return [round($totalAmount, 2)];
        }

        $amounts = [];
        $remaining = $totalAmount;

        // Generate random proportions for each donation
        $proportions = [];
        for ($i = 0; $i < $numberOfDonations; $i++) {
            $proportions[] = fake()->randomFloat(2, 1, 100);
        }

        $totalProportion = array_sum($proportions);

        // Convert proportions to actual amounts
        for ($i = 0; $i < $numberOfDonations - 1; $i++) {
            $amount = round(($proportions[$i] / $totalProportion) * $totalAmount, 2);

            // Ensure minimum donation of 5.00
            $amount = max(5.00, $amount);

            $amounts[] = $amount;
            $remaining -= $amount;
        }

        // Add the remaining amount as the last donation to ensure exact sum
        // Ensure it's at least 5.00
        $lastAmount = max(5.00, round($remaining, 2));
        $amounts[] = $lastAmount;

        // Adjust if the last amount brought us over the total
        $actualTotal = array_sum($amounts);
        if ($actualTotal !== $totalAmount) {
            $difference = $actualTotal - $totalAmount;
            $amounts[count($amounts) - 1] -= $difference;
            $amounts[count($amounts) - 1] = round($amounts[count($amounts) - 1], 2);
        }

        return $amounts;
    }

    /**
     * Generate a random datetime between two dates
     *
     * @param \Illuminate\Support\Carbon|null $startDate
     * @param \Illuminate\Support\Carbon $endDate
     * @return \Illuminate\Support\Carbon
     */
    private function randomDateBetween($startDate, $endDate): \Illuminate\Support\Carbon
    {
        if (!$startDate) {
            $startDate = now()->subMonths(6);
        }

        $startTimestamp = $startDate->timestamp;
        $endTimestamp = $endDate->timestamp;

        $randomTimestamp = fake()->numberBetween($startTimestamp, $endTimestamp);

        return \Illuminate\Support\Carbon::createFromTimestamp($randomTimestamp);
    }
}
