<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Contracts\Campaign\CampaignStatusValidatorInterface;
use App\DTOs\Campaign\UpdateCampaignDTO;
use App\Enums\Campaign\CampaignStatus;
use App\Models\Campaign\Campaign;

/**
 * Campaign Status Validator
 *
 * Validates campaign status transitions and required fields
 * Follows Single Responsibility Principle - only handles status validation
 * Follows Open/Closed Principle - new validation rules can be added by extending this class
 */
class CampaignStatusValidator implements CampaignStatusValidatorInterface
{
    /**
     * Statuses that require validation
     */
    private const STATUSES_REQUIRING_VALIDATION = [
        CampaignStatus::WAITING_FOR_VALIDATION,
        CampaignStatus::ACTIVE,
    ];

    /**
     * Required fields for specific statuses
     */
    private const REQUIRED_FIELDS = [
        'goal_amount' => 'goalAmount',
        'currency' => 'currency',
        'start_date' => 'startDate',
        'end_date' => 'endDate',
    ];

    /**
     * Validate a status transition
     *
     * @param Campaign $campaign
     * @param CampaignStatus|null $newStatus
     * @param UpdateCampaignDTO $dto
     * @return void
     * @throws \InvalidArgumentException
     */
    public function validateStatusTransition(
        Campaign $campaign,
        ?CampaignStatus $newStatus,
        UpdateCampaignDTO $dto
    ): void {
        // No validation needed if status is not changing or is null
        if ($newStatus === null || $newStatus === $campaign->status) {
            return;
        }

        // Check if the new status requires validation
        if (!$this->requiresValidation($newStatus)) {
            return;
        }

        // Determine validation strategy based on transition
        $requireInDto = $this->shouldRequireFieldsInDto($campaign->status, $newStatus);

        // Validate required fields
        $missingFields = $this->getMissingFields($campaign, $dto, $requireInDto);

        if (!empty($missingFields)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot change status to %s without required fields: %s',
                    $newStatus->value,
                    implode(', ', $missingFields)
                )
            );
        }
    }

    /**
     * Get required fields for a specific status
     *
     * @param CampaignStatus $status
     * @return array<string, string>
     */
    public function getRequiredFieldsForStatus(CampaignStatus $status): array
    {
        if (!$this->requiresValidation($status)) {
            return [];
        }

        return self::REQUIRED_FIELDS;
    }

    /**
     * Check if a status transition requires validation
     *
     * @param CampaignStatus|null $newStatus
     * @return bool
     */
    public function requiresValidation(?CampaignStatus $newStatus): bool
    {
        if ($newStatus === null) {
            return false;
        }

        return in_array($newStatus, self::STATUSES_REQUIRING_VALIDATION, true);
    }

    /**
     * Determine if fields must be in DTO or can be in campaign
     *
     * @param CampaignStatus $oldStatus
     * @param CampaignStatus $newStatus
     * @return bool
     */
    private function shouldRequireFieldsInDto(CampaignStatus $oldStatus, CampaignStatus $newStatus): bool
    {
        // When changing FROM draft TO waiting_for_validation, require fields in DTO
        return $oldStatus === CampaignStatus::DRAFT &&
               $newStatus === CampaignStatus::WAITING_FOR_VALIDATION;
    }

    /**
     * Get missing required fields
     *
     * @param Campaign $campaign
     * @param UpdateCampaignDTO $dto
     * @param bool $requireInDto
     * @return array<int, string>
     */
    private function getMissingFields(Campaign $campaign, UpdateCampaignDTO $dto, bool $requireInDto): array
    {
        $missingFields = [];

        foreach (self::REQUIRED_FIELDS as $fieldName => $dtoProperty) {
            $dtoValue = $dto->{$dtoProperty};

            if ($requireInDto) {
                // Must be in the DTO and not null
                if ($dtoValue === null) {
                    $missingFields[] = $fieldName;
                }
            } else {
                // Can be in DTO or already in campaign
                $campaignValue = $campaign->{$fieldName};

                if ($dtoValue === null && ($campaignValue === null || $campaignValue === '')) {
                    $missingFields[] = $fieldName;
                }
            }
        }

        return $missingFields;
    }
}
