<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Contracts\Campaign\CampaignReadRepositoryInterface;
use App\Contracts\Campaign\CampaignStatusValidatorInterface;
use App\Contracts\Campaign\CampaignWriteRepositoryInterface;
use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Contracts\Tag\TagWriteServiceInterface;
use App\DTOs\Campaign\CampaignDTO;
use App\DTOs\Campaign\UpdateCampaignDTO;
use App\Models\Campaign\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Campaign Write Service
 *
 * Handles all write operations for campaigns (create, update, delete)
 * Follows Single Responsibility Principle - only writes
 * Follows Dependency Inversion Principle - depends on abstractions (interfaces)
 */
class CampaignWriteService implements CampaignWriteServiceInterface
{
    /**
     * Constructor
     *
     * @param CampaignReadRepositoryInterface $readRepository
     * @param CampaignWriteRepositoryInterface $writeRepository
     * @param CampaignStatusValidatorInterface $statusValidator
     * @param TagWriteServiceInterface $tagWriteService
     */
    public function __construct(
        private CampaignReadRepositoryInterface $readRepository,
        private CampaignWriteRepositoryInterface $writeRepository,
        private CampaignStatusValidatorInterface $statusValidator,
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

            // Create campaign using repository
            $campaign = $this->writeRepository->create($data);

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

            $campaign = $this->readRepository->findOrFail($id);

            // Convert DTO to array for database operations
            $data = $dto->toArray();

            // Get tags from DTO (if present)
            $tagNames = $dto->tags;

            // Validate status transition using dedicated validator
            $this->statusValidator->validateStatusTransition($campaign, $dto->status, $dto);

            // Update campaign with data from DTO using repository
            $this->writeRepository->update($campaign, $data);

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

            $campaign = $this->readRepository->findOrFail($id);
            $deleted = $this->writeRepository->delete($campaign);

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
