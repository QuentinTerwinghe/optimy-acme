<?php

declare(strict_types=1);

namespace App\Mappers\Campaign;

use App\DTOs\Campaign\CampaignDTO;
use App\Enums\Campaign\CampaignStatus;
use App\Enums\Common\Currency;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
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
            goalAmount: isset($validated['goal_amount']) ? (float) $validated['goal_amount'] : null,
            currency: isset($validated['currency']) ? Currency::from($validated['currency']) : null,
            startDate: isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null,
            endDate: isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null,
            status: CampaignStatus::fromRequest($validated['status'] ?? 'draft'),
            description: $validated['description'] ?? null,
            categoryId: $validated['category_id'] ?? null,
            currentAmount: isset($validated['current_amount']) ? (float) $validated['current_amount'] : null,
            tags: $validated['tags'] ?? null
        );
    }

    /**
     * Convert UpdateCampaignRequest to array for update
     * Only includes fields that are present in the request
     *
     * @return array<string, mixed>
     */
    public static function fromUpdateRequest(UpdateCampaignRequest $request): array
    {
        $validated = $request->validated();
        $data = [];

        if (isset($validated['title'])) {
            $data['title'] = $validated['title'];
        }

        if (isset($validated['description'])) {
            $data['description'] = $validated['description'];
        }

        if (isset($validated['goal_amount'])) {
            $data['goal_amount'] = (float) $validated['goal_amount'];
        }

        if (isset($validated['currency'])) {
            $data['currency'] = $validated['currency'];
        }

        if (isset($validated['start_date'])) {
            $data['start_date'] = Carbon::parse($validated['start_date'])->toDateTimeString();
        }

        if (isset($validated['end_date'])) {
            $data['end_date'] = Carbon::parse($validated['end_date'])->toDateTimeString();
        }

        if (isset($validated['status'])) {
            $data['status'] = $validated['status'];
        }

        if (isset($validated['category_id'])) {
            $data['category_id'] = $validated['category_id'];
        }

        if (isset($validated['current_amount'])) {
            $data['current_amount'] = (float) $validated['current_amount'];
        }

        if (isset($validated['tags'])) {
            $data['tags'] = $validated['tags'];
        }

        return $data;
    }
}
