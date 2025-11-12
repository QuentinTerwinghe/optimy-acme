<?php

namespace App\Enums\Payment;

enum PaymentMethodEnum: string
{
    case FAKE = 'fake';
    case PAYPAL = 'paypal';
    case CREDIT_CARD = 'credit_card';

    /**
     * Get all available payment methods.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::FAKE => 'Fake Payment (Test)',
            self::PAYPAL => 'PayPal',
            self::CREDIT_CARD => 'Credit Card',
        };
    }

    /**
     * Check if this is a test payment method.
     */
    public function isTest(): bool
    {
        return $this === self::FAKE;
    }

    /**
     * Check if this payment method is currently enabled.
     * In the future, this could check against a configuration or database.
     */
    public function isEnabled(): bool
    {
        return match ($this) {
            self::FAKE => true, // Only fake is enabled for now
            self::PAYPAL, self::CREDIT_CARD => false, // Future payment methods
        };
    }
}
