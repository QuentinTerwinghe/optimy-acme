<?php

declare(strict_types=1);

namespace App\DTOs\Campaign;

use App\Enums\CampaignStatus;
use App\Enums\Currency;
use Carbon\Carbon;

/**
 * Data Transfer Object for Campaign creation/update
 *
 * Provides type safety and clear contract between layers
 */
final readonly class CampaignDTO
{
    public function __construct(
        public string $title,
        public float $goalAmount,
        public Currency $currency,
        public Carbon $startDate,
        public Carbon $endDate,
        public CampaignStatus $status,
        public ?string $description = null,
        public ?int $categoryId = null,
        public ?float $currentAmount = null,
        /** @var array<string>|null */
        public ?array $tags = null,
    ) {}

    /**
     * Convert DTO to array for database operations
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'goal_amount' => $this->goalAmount,
            'current_amount' => $this->currentAmount ?? 0,
            'currency' => $this->currency->value,
            'start_date' => $this->startDate->toDateTimeString(),
            'end_date' => $this->endDate->toDateTimeString(),
            'status' => $this->status->value,
            'category_id' => $this->categoryId,
        ];
    }
}
