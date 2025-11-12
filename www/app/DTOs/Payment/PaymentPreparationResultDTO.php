<?php

namespace App\DTOs\Payment;

readonly class PaymentPreparationResultDTO
{
    /**
     * @param array<string, mixed> $payload The payload to send to the payment gateway (including callback URL)
     * @param string $redirectUrl The URL where the user should be redirected to complete the payment
     */
    public function __construct(
        public array $payload,
        public string $redirectUrl,
    ) {}
}
