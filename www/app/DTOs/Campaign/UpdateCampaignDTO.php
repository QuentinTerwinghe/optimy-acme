<?php

declare(strict_types=1);

namespace App\DTOs\Campaign;

use App\Enums\Campaign\CampaignStatus;
use App\Enums\Common\Currency;
use Carbon\Carbon;

/**
 * Data Transfer Object for Campaign updates
 *
 * All fields are optional to support partial updates
 * Provides type safety and clear contract between layers
 */
final readonly class UpdateCampaignDTO
{
    public function __construct(
        public ?string $title = null,
        public ?float $goalAmount = null,
        public ?Currency $currency = null,
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null,
        public ?CampaignStatus $status = null,
        public ?string $description = null,
        public ?int $categoryId = null,
        public ?float $currentAmount = null,
        /** @var array<string>|null */
        public ?array $tags = null,
    ) {}

    /**
     * Convert DTO to array for database operations
     * Only includes fields that are not null (for partial updates)
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->goalAmount !== null) {
            $data['goal_amount'] = $this->goalAmount;
        }

        if ($this->currentAmount !== null) {
            $data['current_amount'] = $this->currentAmount;
        }

        if ($this->currency !== null) {
            $data['currency'] = $this->currency->value;
        }

        if ($this->startDate !== null) {
            $data['start_date'] = $this->startDate->toDateTimeString();
        }

        if ($this->endDate !== null) {
            $data['end_date'] = $this->endDate->toDateTimeString();
        }

        if ($this->status !== null) {
            $data['status'] = $this->status->value;
        }

        if ($this->categoryId !== null) {
            $data['category_id'] = $this->categoryId;
        }

        return $data;
    }

    /**
     * Check if any field is set (not null)
     */
    public function hasAnyField(): bool
    {
        return $this->title !== null
            || $this->description !== null
            || $this->goalAmount !== null
            || $this->currentAmount !== null
            || $this->currency !== null
            || $this->startDate !== null
            || $this->endDate !== null
            || $this->status !== null
            || $this->categoryId !== null
            || $this->tags !== null;
    }
}
