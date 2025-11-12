<?php

namespace App\Contracts\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;

/**
 * Interface for payment processing DTO.
 */
interface ProcessPaymentDTOInterface
{
    /**
     * Transform array of data to DTO
     *
     * @param array<string, mixed> $data
     *
     * @return self
     */
    public static function fromArray(array $data): self;

    /**
     * Transform DTO object to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
