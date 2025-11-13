<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Donation\DonationStatus;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use Illuminate\Database\Seeder;

class DonationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates donations and corresponding payments for each campaign, ensuring that:
     * 1. The sum of successful donations equals the campaign's current_amount
     * 2. Multiple donations are created to simulate realistic donation patterns
     * 3. A mix of successful and failed donations are included
     * 4. Each donation has a corresponding payment record with appropriate status
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
                $createdAt = $this->randomDateBetween($campaign->start_date, now());

                $donation = Donation::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $users->random()->id,
                    'amount' => $amount,
                    'status' => DonationStatus::SUCCESS,
                    'error_message' => null,
                    'created_by' => $users->random()->id,
                    'updated_by' => $users->random()->id,
                    'created_at' => $createdAt,
                ]);

                // Create corresponding completed payment
                Payment::create([
                    'donation_id' => $donation->id,
                    'payment_method' => PaymentMethodEnum::FAKE,
                    'status' => PaymentStatusEnum::COMPLETED,
                    'amount' => $amount,
                    'currency' => 'EUR',
                    'transaction_id' => 'txn_' . fake()->uuid(),
                    'gateway_response' => json_encode(['status' => 'success']),
                    'prepared_at' => $createdAt,
                    'initiated_at' => $createdAt->copy()->addSeconds(1),
                    'completed_at' => $createdAt->copy()->addSeconds(2),
                    'created_at' => $createdAt,
                ]);
            }

            // Add some failed donations (0-2 per campaign)
            $failedCount = fake()->numberBetween(0, 2);
            for ($i = 0; $i < $failedCount; $i++) {
                $failedAmount = fake()->randomFloat(2, 10, 1000);
                $failedAt = $this->randomDateBetween($campaign->start_date, now());
                $errorMessage = fake()->randomElement([
                    'Payment gateway timeout',
                    'Insufficient funds',
                    'Card declined',
                    'Payment processor error',
                    'Invalid card details',
                    'Transaction limit exceeded',
                ]);

                $donation = Donation::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $users->random()->id,
                    'amount' => $failedAmount,
                    'status' => DonationStatus::FAILED,
                    'error_message' => $errorMessage,
                    'created_by' => $users->random()->id,
                    'updated_by' => $users->random()->id,
                    'created_at' => $failedAt,
                ]);

                // Create corresponding failed payment
                Payment::create([
                    'donation_id' => $donation->id,
                    'payment_method' => PaymentMethodEnum::FAKE,
                    'status' => PaymentStatusEnum::FAILED,
                    'amount' => $failedAmount,
                    'currency' => 'EUR',
                    'error_message' => $errorMessage,
                    'error_code' => fake()->randomElement(['ERR_001', 'ERR_002', 'ERR_003', 'ERR_004']),
                    'gateway_response' => json_encode(['status' => 'failed', 'message' => $errorMessage]),
                    'prepared_at' => $failedAt,
                    'initiated_at' => $failedAt->copy()->addSeconds(1),
                    'failed_at' => $failedAt->copy()->addSeconds(2),
                    'created_at' => $failedAt,
                ]);
            }
        }

        $this->command->info('Donations and payments seeded successfully!');
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
        // Ensure it's at least 5.00, but if remaining is less than 5.00, adjust the previous amounts
        $lastAmount = round($remaining, 2);

        if ($lastAmount < 5.00) {
            // Need to redistribute - take from previous amounts to meet minimum
            $deficit = 5.00 - $lastAmount;
            $lastAmount = 5.00;

            // Redistribute deficit across previous donations
            $redistributeAmount = round($deficit / (count($amounts)), 2);
            for ($i = 0; $i < count($amounts); $i++) {
                if ($amounts[$i] > 5.00 + $redistributeAmount) {
                    $amounts[$i] -= $redistributeAmount;
                    $amounts[$i] = round($amounts[$i], 2);
                }
            }
        }

        $amounts[] = $lastAmount;

        // Adjust if the total doesn't match exactly
        $actualTotal = array_sum($amounts);
        if (abs($actualTotal - $totalAmount) > 0.01) {
            $difference = $actualTotal - $totalAmount;
            // Find the largest amount to adjust
            $maxIndex = array_search(max($amounts), $amounts);
            $amounts[$maxIndex] -= $difference;
            $amounts[$maxIndex] = round($amounts[$maxIndex], 2);

            // Ensure the adjusted amount is still positive
            if ($amounts[$maxIndex] < 5.00) {
                // Fall back to simple distribution
                $amounts = [];
                $amountPerDonation = round($totalAmount / $numberOfDonations, 2);
                for ($i = 0; $i < $numberOfDonations - 1; $i++) {
                    $amounts[] = $amountPerDonation;
                }
                $amounts[] = round($totalAmount - (array_sum($amounts)), 2);
            }
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
