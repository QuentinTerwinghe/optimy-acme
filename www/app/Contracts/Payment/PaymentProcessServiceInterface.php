<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Exceptions\Payment\PaymentProcessingException;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;

/**
 * Interface for payment processing service.
 * Handles the creation of donations and payments in pending status.
 */
interface PaymentProcessServiceInterface
{
    /**
     * Initialize payment process by creating donation and payment records.
     *
     * @param string $campaignId The campaign to donate to
     * @param string $userId The user making the donation
     * @param float $amount The donation amount
     * @param PaymentMethodEnum $paymentMethod The selected payment method
     * @param array<string, mixed> $metadata Additional metadata for the payment
     * @return array{donation: Donation, payment: Payment}
     * @throws PaymentProcessingException
     */
    public function initializePayment(
        string $campaignId,
        string $userId,
        float $amount,
        PaymentMethodEnum $paymentMethod,
        array $metadata = []
    ): array;
}
