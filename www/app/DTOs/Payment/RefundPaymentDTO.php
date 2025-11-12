<?php

declare(strict_types=1);

namespace App\DTOs\Payment;

/**
 * DTO for refunding payment data.
 */
final readonly class RefundPaymentDTO
{
    /**
     * @param float|null $amount Amount to refund (null for full refund)
     * @param string|null $reason Reason for the refund
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public ?float $amount = null,
        public ?string $reason = null,
        public array $metadata = [],
    ) {}

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            reason: $data['reason'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
        ];
    }
}
