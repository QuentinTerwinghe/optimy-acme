<?php

namespace App\Enums\Payment;

enum FailureReasonEnum: string
{
    case CARD_DECLINED = 'card_declined';
    case PAYMENT_GATEWAY_TIMEOUT = 'payment_gateway_timeout';
    case INSUFFICIENT_FUNDS = 'insufficient_funds';
    case REJECTED_BY_BANK = 'rejected_by_bank';

    /**
     * Get all available failure reasons.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the failure reason.
     */
    public function label(): string
    {
        return match ($this) {
            self::CARD_DECLINED => 'Card Declined',
            self::PAYMENT_GATEWAY_TIMEOUT => 'Payment Gateway Timeout',
            self::INSUFFICIENT_FUNDS => 'Insufficient Funds',
            self::REJECTED_BY_BANK => 'Rejected by Bank',
        };
    }

    /**
     * Get the error code for this failure reason.
     */
    public function errorCode(): string
    {
        return match ($this) {
            self::CARD_DECLINED => 'CARD_DECLINED',
            self::PAYMENT_GATEWAY_TIMEOUT => 'GATEWAY_TIMEOUT',
            self::INSUFFICIENT_FUNDS => 'INSUFFICIENT_FUNDS',
            self::REJECTED_BY_BANK => 'BANK_REJECTION',
        };
    }
}
