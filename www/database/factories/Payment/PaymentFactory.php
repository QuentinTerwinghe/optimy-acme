<?php

declare(strict_types=1);

namespace Database\Factories\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Payment>
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donation_id' => Donation::factory(),
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'USD',
            'transaction_id' => null,
            'gateway_response' => null,
            'error_message' => null,
            'error_code' => null,
            'metadata' => null,
            'payload' => null,
            'redirect_url' => null,
            'prepared_at' => null,
            'initiated_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
            'refunded_at' => null,
        ];
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatusEnum::PENDING,
            'initiated_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
            'refunded_at' => null,
        ]);
    }

    /**
     * Indicate that the payment is prepared.
     */
    public function prepared(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatusEnum::PENDING,
            'payload' => [
                'session_id' => 'FAKE_SESSION_' . strtoupper($this->faker->bothify('??##??##??##??##')),
                'gateway' => 'fake',
            ],
            'redirect_url' => 'https://fake-gateway.example.com/checkout/' . $this->faker->uuid(),
            'prepared_at' => now(),
            'initiated_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatusEnum::PROCESSING,
            'initiated_at' => now()->subMinutes(2),
        ]);
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatusEnum::COMPLETED,
            'transaction_id' => 'FAKE_' . strtoupper($this->faker->bothify('??##??##??##??##')),
            'gateway_response' => json_encode([
                'gateway' => 'fake',
                'simulated' => true,
                'timestamp' => now()->toISOString(),
            ]),
            'initiated_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatusEnum::FAILED,
            'error_message' => $this->faker->sentence(),
            'error_code' => 'FAKE_ERROR_' . $this->faker->numberBetween(100, 999),
            'gateway_response' => json_encode([
                'gateway' => 'fake',
                'simulated' => true,
                'error' => true,
                'timestamp' => now()->toISOString(),
            ]),
            'initiated_at' => now()->subMinutes(5),
            'failed_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment has been refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatusEnum::REFUNDED,
            'transaction_id' => 'REFUND_' . strtoupper($this->faker->bothify('??##??##??##??##')),
            'gateway_response' => json_encode([
                'gateway' => 'fake',
                'simulated' => true,
                'refunded' => true,
                'timestamp' => now()->toISOString(),
            ]),
            'initiated_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
            'refunded_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment uses the fake payment method.
     */
    public function fake(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);
    }

    /**
     * Indicate that the payment uses PayPal (for future testing).
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethodEnum::PAYPAL,
        ]);
    }

    /**
     * Indicate that the payment uses credit card (for future testing).
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethodEnum::CREDIT_CARD,
        ]);
    }

    /**
     * Set a specific amount for the payment.
     */
    public function amount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}
