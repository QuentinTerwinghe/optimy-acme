<?php

declare(strict_types=1);

namespace App\DTOs\Payment;

use App\Contracts\Payment\ProcessPaymentDTOInterface;

/**
 * DTO for processing payment data.
 */
final readonly class FakeProcessPaymentDTO implements ProcessPaymentDTOInterface
{
    /**
     * @param array<string, mixed> $metadata Additional metadata for the payment
     * @param bool $simulateFailure For testing: simulate payment failure
     * @param string|null $errorMessage For testing: custom error message
     * @param string|null $errorCode For testing: custom error code
     * @param int $processingDelay For testing: delay in seconds
     * @param string|null $cardLastFour Last 4 digits of card (for credit card payments)
     * @param string|null $cardBrand Card brand (Visa, Mastercard, etc.)
     * @param string|null $paypalEmail PayPal email address
     * @param string|null $paypalPayerId PayPal payer ID
     */
    public function __construct(
        public array $metadata = [],
        public bool $simulateFailure = false,
        public ?string $errorMessage = null,
        public ?string $errorCode = null,
        public int $processingDelay = 0,
        public ?string $cardLastFour = null,
        public ?string $cardBrand = null,
        public ?string $paypalEmail = null,
        public ?string $paypalPayerId = null,
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
            metadata: $data['metadata'] ?? [],
            simulateFailure: $data['simulate_failure'] ?? false,
            errorMessage: $data['error_message'] ?? null,
            errorCode: $data['error_code'] ?? null,
            processingDelay: $data['processing_delay'] ?? 0,
            cardLastFour: $data['card_last_four'] ?? null,
            cardBrand: $data['card_brand'] ?? null,
            paypalEmail: $data['paypal_email'] ?? null,
            paypalPayerId: $data['paypal_payer_id'] ?? null,
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
            'metadata' => $this->metadata,
            'simulate_failure' => $this->simulateFailure,
            'error_message' => $this->errorMessage,
            'error_code' => $this->errorCode,
            'processing_delay' => $this->processingDelay,
            'card_last_four' => $this->cardLastFour,
            'card_brand' => $this->cardBrand,
            'paypal_email' => $this->paypalEmail,
            'paypal_payer_id' => $this->paypalPayerId,
        ];
    }
}
