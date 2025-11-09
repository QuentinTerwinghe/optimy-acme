<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Contracts\Tag\TagWriteServiceInterface;
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
     * @param array<string, mixed> $data
     * @return Campaign
     * @throws \Exception
     */
    public function createCampaign(array $data): Campaign
    {
        try {
            DB::beginTransaction();

            // Extract tags from data (if present)
            $tagNames = $data['tags'] ?? [];
            unset($data['tags']);

            // Create campaign
            $campaign = Campaign::create($data);

            // Handle tags if provided
            if (!empty($tagNames) && is_array($tagNames)) {
                $tags = $this->tagWriteService->findOrCreateTags($tagNames);
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
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing campaign
     *
     * @param string $id
     * @param array<string, mixed> $data
     * @return Campaign
     * @throws \Exception
     */
    public function updateCampaign(string $id, array $data): Campaign
    {
        try {
            DB::beginTransaction();

            $campaign = Campaign::findOrFail($id);

            // Extract tags from data (if present)
            $tagNames = $data['tags'] ?? null;
            unset($data['tags']);

            // Update campaign
            $campaign->update($data);

            // Handle tags if provided (null means don't update tags, empty array means remove all tags)
            if ($tagNames !== null && is_array($tagNames)) {
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
                'data' => $data,
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

            $campaign = Campaign::findOrFail($id);
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
