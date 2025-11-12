<?php

declare(strict_types=1);

namespace App\Policies\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Auth\User;
use App\Models\Payment\Payment;

/**
 * Policy for managing fake payment authorization
 *
 * Defines authorization rules for accessing the fake payment service page
 */
class FakePaymentPolicy
{
    /**
     * Determine if the user can access the fake payment service page
     *
     * A user can access the fake payment page only if:
     * - The user is authenticated
     * - The payment belongs to the user (through donation)
     * - The payment status is PENDING
     * - The payment method is FAKE
     *
     * @param User $user The user attempting to access the page
     * @param Payment $payment The payment to process
     * @return bool True if user can access, false otherwise
     */
    public function access(User $user, Payment $payment): bool
    {
        // Check if payment has a donation
        if ($payment->donation === null) {
            return false;
        }

        // Check if payment belongs to the user
        if ($payment->donation->user_id !== $user->id) {
            return false;
        }

        // Check if payment is pending
        if ($payment->status !== PaymentStatusEnum::PENDING) {
            return false;
        }

        // Check if payment method is FAKE
        if ($payment->payment_method !== PaymentMethodEnum::FAKE) {
            return false;
        }

        return true;
    }
}
