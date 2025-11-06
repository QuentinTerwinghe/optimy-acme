<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Campaign Resource
 *
 * Transforms Campaign model data for API responses
 *
 * @property \App\Models\Campaign $resource
 */
class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'goal_amount' => number_format((float) $this->resource->goal_amount, 2, '.', ''),
            'current_amount' => number_format((float) $this->resource->current_amount, 2, '.', ''),
            'currency' => $this->resource->currency->value,
            'start_date' => $this->resource->start_date->toIso8601String(),
            'end_date' => $this->resource->end_date->toIso8601String(),
            'end_date_formatted' => $this->resource->end_date->format('M d, Y'),
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->label(),
            'progress_percentage' => $this->calculateProgressPercentage(),
            'days_remaining' => $this->calculateDaysRemaining(),
        ];
    }

    /**
     * Calculate the progress percentage
     */
    private function calculateProgressPercentage(): float
    {
        $goal = (float) $this->resource->goal_amount;
        $current = (float) $this->resource->current_amount;

        if ($goal <= 0) {
            return 0;
        }

        $percentage = ($current / $goal) * 100;

        return round(min($percentage, 100), 2);
    }

    /**
     * Calculate days remaining until campaign end
     */
    private function calculateDaysRemaining(): int
    {
        return (int) max(0, now()->diffInDays($this->resource->end_date, false));
    }
}
