<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

use App\DTOs\Campaign\UpdateCampaignDTO;
use App\Enums\Campaign\CampaignStatus;
use App\Models\Campaign\Campaign;

/**
 * Campaign Status Validator Interface
 *
 * Defines the contract for validating campaign status transitions
 * Follows Single Responsibility Principle - focused on status validation
 * Follows Open/Closed Principle - new validation rules can be added without modifying existing code
 */
interface CampaignStatusValidatorInterface
{
    /**
     * Validate a status transition
     *
     * Checks if the campaign can transition from its current status to the new status
     * and validates that all required fields are present
     *
     * @param Campaign $campaign The campaign to validate
     * @param CampaignStatus|null $newStatus The new status
     * @param UpdateCampaignDTO $dto The data being updated
     * @return void
     * @throws \InvalidArgumentException If the transition is invalid or required fields are missing
     */
    public function validateStatusTransition(
        Campaign $campaign,
        ?CampaignStatus $newStatus,
        UpdateCampaignDTO $dto
    ): void;

    /**
     * Get required fields for a specific status
     *
     * @param CampaignStatus $status
     * @return array<string, string> Field name => DTO property name mapping
     */
    public function getRequiredFieldsForStatus(CampaignStatus $status): array;

    /**
     * Check if a status transition requires validation
     *
     * @param CampaignStatus|null $newStatus
     * @return bool
     */
    public function requiresValidation(?CampaignStatus $newStatus): bool;
}
