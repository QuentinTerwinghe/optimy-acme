<?php

declare(strict_types=1);

namespace App\Enums;

enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case WAITING_FOR_VALIDATION = 'waiting_for_validation';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Get all possible values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::WAITING_FOR_VALIDATION => 'Waiting for Validation',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Create from request status value
     * Maps user-friendly status names to enum cases
     */
    public static function fromRequest(string $status): self
    {
        return match ($status) {
            'draft' => self::DRAFT,
            'waiting_for_validation' => self::WAITING_FOR_VALIDATION,
            'active' => self::ACTIVE,
            'completed' => self::COMPLETED,
            'cancelled' => self::CANCELLED,
            default => throw new \InvalidArgumentException("Invalid status: {$status}"),
        };
    }
}
