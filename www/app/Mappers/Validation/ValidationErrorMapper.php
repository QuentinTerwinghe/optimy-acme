<?php

declare(strict_types=1);

namespace App\Mappers\Validation;

/**
 * Validation Error Mapper
 *
 * Maps exception messages to validation error format
 * Follows Single Responsibility Principle - only handles error transformation
 */
class ValidationErrorMapper
{
    /**
     * Parse InvalidArgumentException message and convert to validation error format
     *
     * Extracts field names from exception messages like:
     * "Cannot update campaign status to waiting_for_validation without required fields: title, description"
     *
     * @param \InvalidArgumentException $exception
     * @return array{message: string, errors: array<string, array<int, string>>}
     */
    public static function parseInvalidArgumentException(\InvalidArgumentException $exception): array
    {
        $message = $exception->getMessage();
        $errors = [];

        // Extract missing fields from the message
        if (str_contains($message, 'without required fields:')) {
            $fieldsString = substr($message, strpos($message, 'without required fields:') + 25);
            $fields = array_map('trim', explode(',', $fieldsString));

            foreach ($fields as $field) {
                $fieldName = str_replace('_', ' ', $field);
                $errors[$field] = [ucfirst($fieldName) . ' is required'];
            }
        }

        // Generate appropriate message based on error count
        $errorMessage = match (count($errors)) {
            0 => $message, // No structured errors, return original message
            1 => $errors[array_key_first($errors)][0], // Single error
            default => array_keys($errors)[0] . ' is required (and ' . (count($errors) - 1) . ' more errors)', // Multiple errors
        };

        return [
            'message' => $errorMessage,
            'errors' => $errors,
        ];
    }

    /**
     * Check if exception message contains field validation errors
     *
     * @param \InvalidArgumentException $exception
     * @return bool
     */
    public static function hasFieldValidationErrors(\InvalidArgumentException $exception): bool
    {
        return str_contains($exception->getMessage(), 'without required fields:');
    }
}
