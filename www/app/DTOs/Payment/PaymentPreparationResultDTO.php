<?php

namespace App\DTOs\Payment;

readonly class PaymentPreparationResultDTO
{
    /**
     * @param array<string, mixed> $payload The payload to send to the payment gateway
     * @param string $redirectUrl The URL where the user should be redirected
     */
    public function __construct(
        public array $payload,
        public string $redirectUrl,
    ) {}
}
