<?php

declare(strict_types=1);

namespace App\Enums\Campaign;

enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case WAITING_FOR_VALIDATION = 'waiting_for_validation';
    case ACTIVE = 'active';
    case REJECTED = 'rejected';
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
            self::REJECTED => 'Rejected',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Create from request status value
     * Maps user-friendly status names to enum cases
     *
     * @param string $status
     * @return CampaignStatus
     */
    public static function fromRequest(string $status): self
    {
        return match ($status) {
            'draft' => self::DRAFT,
            'waiting_for_validation' => self::WAITING_FOR_VALIDATION,
            'active' => self::ACTIVE,
            'rejected' => self::REJECTED,
            'completed' => self::COMPLETED,
            'cancelled' => self::CANCELLED,
            default => throw new \InvalidArgumentException("Invalid status: {$status}"),
        };
    }

    /**
     * Get statuses that allow campaign editing
     *
     * Campaigns can only be edited when they are in:
     * - DRAFT: Initial creation state
     * - WAITING_FOR_VALIDATION: Submitted but not yet approved
     * - REJECTED: Rejected and can be resubmitted
     *
     * @return array<int, CampaignStatus>
     */
    public static function getEditableStatuses(): array
    {
        return [
            self::DRAFT,
            self::WAITING_FOR_VALIDATION,
            self::REJECTED,
        ];
    }
}
