<?php

declare(strict_types=1);

namespace App\Enums\Donation;

enum DonationStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';

    /**
     * Get all possible values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Check if the donation is successful
     */
    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Check if the donation has failed
     */
    public function hasFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Check if the donation is still pending
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
