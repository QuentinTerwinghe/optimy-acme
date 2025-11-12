<?php

namespace App\Contracts\Payment;

use App\DTOs\Payment\PaymentPreparationResultDTO;
use App\Models\Payment\Payment;

interface PaymentMethodHandlerInterface
{
    /**
     * Prepare the payment by generating the payload and redirect URL
     *
     * @param Payment $payment The payment record to prepare
     * @return PaymentPreparationResultDTO The preparation result containing payload and redirect URL
     */
    public function prepare(Payment $payment): PaymentPreparationResultDTO;
}
