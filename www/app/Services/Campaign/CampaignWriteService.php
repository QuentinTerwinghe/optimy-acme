<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Contracts\Tag\TagWriteServiceInterface;
use App\DTOs\Campaign\CampaignDTO;
use App\DTOs\Campaign\UpdateCampaignDTO;
use App\Enums\Campaign\CampaignStatus;
use App\Models\Campaign\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Campaign Write Service
 *
 * Handles all write operations for campaigns (create, update, delete)
 * Follows Single Responsibility Principle - only writes
 */
class CampaignWriteService implements CampaignWriteServiceInterface
{
    /**
     * Constructor
     *
     * @param TagWriteServiceInterface $tagWriteService
     */
    public function __construct(
        private TagWriteServiceInterface $tagWriteService
    ) {}
    /**
     * Create a new campaign
     *
     * @param CampaignDTO $dto
     * @return Campaign
     * @throws \Exception
     */
    public function createCampaign(CampaignDTO $dto): Campaign
    {
        try {
            DB::beginTransaction();

            // Convert DTO to array for database operations
            $data = $dto->toArray();

            // Create campaign
            $campaign = Campaign::create($data);

            // Handle tags if provided
            if (!empty($dto->tags)) {
                $tags = $this->tagWriteService->findOrCreateTags($dto->tags);
                $campaign->tags()->sync($tags->pluck('id'));

                Log::info('Campaign tags synced', [
                    'campaign_id' => (string) $campaign->id,
                    'tag_count' => $tags->count(),
                ]);
            }

            DB::commit();

            // Reload campaign with relationships
            $campaign->load(['category', 'tags']);

            Log::info('Campaign created successfully', [
                'campaign_id' => (string) $campaign->id,
                'title' => $campaign->title,
            ]);

            return $campaign;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create campaign', [
                'error' => $e->getMessage(),
                'dto' => $dto->toArray(),
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing campaign
     *
     * @param string $id
     * @param UpdateCampaignDTO $dto
     * @return Campaign
     * @throws \Exception
     */
    public function updateCampaign(string $id, UpdateCampaignDTO $dto): Campaign
    {
        try {
            DB::beginTransaction();

            $campaign = Campaign::findById($id)->firstOrFail();

            // Convert DTO to array for database operations
            $data = $dto->toArray();

            // Get tags from DTO (if present)
            $tagNames = $dto->tags;

            // Check if status is being changed to WAITING_FOR_VALIDATION or ACTIVE
            $newStatus = $dto->status;
            $oldStatus = $campaign->status;

            if ($newStatus !== null &&
                ($newStatus === CampaignStatus::WAITING_FOR_VALIDATION || $newStatus === CampaignStatus::ACTIVE) &&
                $newStatus !== $oldStatus) {
                $requiredFields = [
                    'goal_amount' => $dto->goalAmount,
                    'currency' => $dto->currency,
                    'start_date' => $dto->startDate,
                    'end_date' => $dto->endDate,
                ];
                $missingFields = [];

                // When changing FROM draft TO waiting_for_validation, require fields in DTO
                // When validating (draft/waiting -> active), check if fields exist in campaign or DTO
                $requireInDto = ($oldStatus === CampaignStatus::DRAFT &&
                                 $newStatus === CampaignStatus::WAITING_FOR_VALIDATION);

                foreach ($requiredFields as $fieldName => $dtoValue) {
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

                if (!empty($missingFields)) {
                    throw new \InvalidArgumentException(
                        'Cannot change status to ' . $newStatus->value . ' without required fields: ' . implode(', ', $missingFields)
                    );
                }
            }

            // Update campaign with data from DTO
            $campaign->update($data);

            // Handle tags if provided (null means don't update tags, empty array means remove all tags)
            if ($tagNames !== null) {
                if (empty($tagNames)) {
                    // Remove all tags
                    $campaign->tags()->detach();

                    Log::info('Campaign tags removed', [
                        'campaign_id' => $campaign->id,
                    ]);
                } else {
                    // Sync tags
                    $tags = $this->tagWriteService->findOrCreateTags($tagNames);
                    $campaign->tags()->sync($tags->pluck('id'));

                    Log::info('Campaign tags synced', [
                        'campaign_id' => $campaign->id,
                        'tag_count' => $tags->count(),
                    ]);
                }
            }

            DB::commit();

            // Reload campaign with relationships
            $campaign->load(['category', 'tags']);

            Log::info('Campaign updated successfully', [
                'campaign_id' => $campaign->id,
                'title' => $campaign->title,
            ]);

            return $campaign;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update campaign', [
                'id' => $id,
                'error' => $e->getMessage(),
                'dto' => $dto->toArray(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a campaign
     *
     * @param string $id
     * @return bool
     * @throws \Exception
     */
    public function deleteCampaign(string $id): bool
    {
        try {
            DB::beginTransaction();

            $campaign = Campaign::findById($id)->firstOrFail();
            $deleted = (bool) $campaign->delete();

            DB::commit();

            Log::info('Campaign deleted successfully', [
                'campaign_id' => $id,
            ]);

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete campaign', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
