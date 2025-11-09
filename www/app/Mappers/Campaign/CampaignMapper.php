<?php

declare(strict_types=1);

namespace App\Mappers\Campaign;

use App\DTOs\Campaign\CampaignDTO;
use App\Enums\CampaignStatus;
use App\Enums\Currency;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use Carbon\Carbon;

/**
 * Maps HTTP requests to Campaign DTOs
 *
 * Centralizes the conversion logic between request layer and domain layer
 */
final class CampaignMapper
{
    /**
     * Convert StoreCampaignRequest to CampaignDTO
     */
    public static function fromStoreRequest(StoreCampaignRequest $request): CampaignDTO
    {
        $validated = $request->validated();

        return new CampaignDTO(
            title: $validated['title'],
            goalAmount: (float) $validated['goal_amount'],
            currency: Currency::from($validated['currency']),
            startDate: Carbon::parse($validated['start_date']),
            endDate: Carbon::parse($validated['end_date']),
            status: CampaignStatus::fromRequest($validated['status'] ?? 'draft'),
            description: $validated['description'] ?? null,
            categoryId: $validated['category_id'] ?? null,
            currentAmount: isset($validated['current_amount']) ? (float) $validated['current_amount'] : null,
            tags: $validated['tags'] ?? null,
        );
    }
}
