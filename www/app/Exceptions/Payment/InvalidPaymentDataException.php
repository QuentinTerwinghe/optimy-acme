<?php

namespace App\Exceptions\Payment;

/**
 * Exception thrown when payment data is invalid.
 */
class InvalidPaymentDataException extends PaymentException
{
    /**
     * Create a new exception for invalid data.
     */
    public static function missingField(string $fieldName): self
    {
        return new self("Missing required payment data field: {$fieldName}");
    }

    /**
     * Create exception for invalid field value.
     */
    public static function invalidField(string $fieldName, string $reason): self
    {
        return new self("Invalid payment data for field {$fieldName}: {$reason}");
    }

    /**
     * Create exception for multiple validation errors.
     *
     * @param array<string, string> $errors
     */
    public static function validationFailed(array $errors): self
    {
        $errorMessages = implode(', ', array_map(
            fn ($field, $error) => "{$field}: {$error}",
            array_keys($errors),
            array_values($errors)
        ));

        return new self("Payment data validation failed: {$errorMessages}");
    }
}
