<?php

declare(strict_types=1);

namespace Database\Factories\Donation;

use App\Enums\Donation\DonationStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Donation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'status' => fake()->randomElement(DonationStatus::values()),
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the donation is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DonationStatus::PENDING->value,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the donation is successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DonationStatus::SUCCESS->value,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the donation has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DonationStatus::FAILED->value,
            'error_message' => fake()->sentence(),
        ]);
    }

    /**
     * Set a specific campaign for the donation.
     */
    public function forCampaign(Campaign $campaign): static
    {
        return $this->state(fn (array $attributes) => [
            'campaign_id' => $campaign->id,
        ]);
    }

    /**
     * Set a specific user for the donation.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set a specific amount for the donation.
     */
    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Set a specific error message for the donation.
     */
    public function withError(string $errorMessage): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DonationStatus::FAILED->value,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Indicate that the donation was created by a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
