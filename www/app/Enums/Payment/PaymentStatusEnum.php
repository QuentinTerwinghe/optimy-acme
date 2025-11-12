<?php

namespace App\Enums\Payment;

enum PaymentStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    /**
     * Get all available payment statuses.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the payment status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }

    /**
     * Get a color class for UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::REFUNDED => 'orange',
        };
    }

    /**
     * Check if the status is terminal (no further processing expected).
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::REFUNDED => true,
            self::PENDING, self::PROCESSING => false,
        };
    }

    /**
     * Check if the status is successful.
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if the status indicates a failure.
     */
    public function isFailure(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Get valid transition statuses from the current status.
     *
     * @return array<self>
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::FAILED],
            self::PROCESSING => [self::COMPLETED, self::FAILED],
            self::COMPLETED => [self::REFUNDED],
            self::FAILED => [], // Failed payments cannot transition
            self::REFUNDED => [], // Refunded payments cannot transition
        };
    }

    /**
     * Check if a transition to another status is valid.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->validTransitions(), true);
    }
}
