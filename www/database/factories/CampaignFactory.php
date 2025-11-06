<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Enums\Currency;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+6 months');
        $goalAmount = fake()->randomFloat(2, 1000, 100000);

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(3),
            'goal_amount' => $goalAmount,
            'current_amount' => fake()->randomFloat(2, 0, $goalAmount * 0.8),
            'currency' => fake()->randomElement(Currency::values()),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => fake()->randomElement(CampaignStatus::values()),
        ];
    }

    /**
     * Indicate that the campaign is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::DRAFT->value,
            'current_amount' => 0,
        ]);
    }

    /**
     * Indicate that the campaign is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::ACTIVE->value,
            'start_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'end_date' => fake()->dateTimeBetween('now', '+6 months'),
        ]);
    }

    /**
     * Indicate that the campaign is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $goalAmount = $attributes['goal_amount'] ?? fake()->randomFloat(2, 1000, 100000);

            return [
                'status' => CampaignStatus::COMPLETED->value,
                'current_amount' => $goalAmount,
                'start_date' => fake()->dateTimeBetween('-6 months', '-3 months'),
                'end_date' => fake()->dateTimeBetween('-2 months', '-1 month'),
            ];
        });
    }

    /**
     * Indicate that the campaign is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::CANCELLED->value,
            'end_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the campaign has reached its goal.
     */
    public function goalReached(): static
    {
        return $this->state(function (array $attributes) {
            $goalAmount = $attributes['goal_amount'] ?? fake()->randomFloat(2, 1000, 100000);

            return [
                'current_amount' => $goalAmount,
            ];
        });
    }

    /**
     * Indicate that the campaign has a specific creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Set a specific currency for the campaign.
     */
    public function currency(Currency $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency->value,
        ]);
    }

    /**
     * Set a specific goal amount.
     */
    public function withGoal(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'goal_amount' => $amount,
        ]);
    }

    /**
     * Set a specific current amount.
     */
    public function withCurrentAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'current_amount' => $amount,
        ]);
    }
}
