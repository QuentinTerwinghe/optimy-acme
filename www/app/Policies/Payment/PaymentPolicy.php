<?php

declare(strict_types=1);

namespace App\Policies\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Auth\User;
use App\Models\Payment\Payment;

/**
 * Policy for managing payment authorization
 *
 * Consolidates all payment-related authorization rules
 */
class PaymentPolicy
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
        // Load donation if not already loaded
        if (!$payment->relationLoaded('donation')) {
            try {
                $payment->load('donation');
            } catch (\Exception $e) {
                // If loading fails, deny access
                return false;
            }
        }

        // Check if payment has a donation
        $donation = $payment->donation;
        if (!$donation) {
            return false;
        }

        // Check if payment belongs to the user
        if ($donation->user_id !== $user->id) {
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

    /**
     * Determine if the user can view the payment result page
     *
     * A user can view the payment result page only if:
     * - The user is authenticated
     * - The payment belongs to the user (through donation)
     * - The payment status is either COMPLETED or FAILED (terminal states)
     *
     * @param User $user The user attempting to view the page
     * @param Payment $payment The payment to view
     * @return bool True if user can view, false otherwise
     */
    public function view(User $user, Payment $payment): bool
    {
        // Load donation if not already loaded
        if (!$payment->relationLoaded('donation')) {
            try {
                $payment->load('donation');
            } catch (\Exception $e) {
                // If loading fails, deny access
                return false;
            }
        }

        // Check if payment has a donation
        $donation = $payment->donation;
        if (!$donation) {
            return false;
        }

        // Check if payment belongs to the user
        if ($donation->user_id !== $user->id) {
            return false;
        }

        // Check if payment status is in a terminal state (completed or failed)
        // Only allow viewing result pages for payments that have a final status
        if (!$payment->isCompleted() && !$payment->isFailed()) {
            return false;
        }

        return true;
    }
}
