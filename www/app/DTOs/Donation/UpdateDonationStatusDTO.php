<?php

declare(strict_types=1);

namespace App\DTOs\Donation;

use App\Enums\Donation\DonationStatus;

/**
 * Data Transfer Object for updating donation status.
 * Follows DTO Pattern - encapsulates data transfer between layers.
 * Follows Single Responsibility Principle (SRP) - only carries data.
 */
readonly class UpdateDonationStatusDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param DonationStatus $status The new status
     * @param string|null $errorMessage Optional error message
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public DonationStatus $status,
        public ?string $errorMessage = null,
        public array $metadata = []
    ) {}

    /**
     * Convert DTO to array for database operations.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'status' => $this->status,
        ];

        if ($this->errorMessage !== null) {
            $data['error_message'] = $this->errorMessage;
        }

        return $data;
    }

    /**
     * Create DTO for successful donation.
     *
     * @return self
     */
    public static function forSuccess(): self
    {
        return new self(
            status: DonationStatus::SUCCESS,
            errorMessage: null
        );
    }

    /**
     * Create DTO for failed donation.
     *
     * @param string|null $errorMessage
     * @return self
     */
    public static function forFailure(?string $errorMessage = null): self
    {
        return new self(
            status: DonationStatus::FAILED,
            errorMessage: $errorMessage
        );
    }
}
