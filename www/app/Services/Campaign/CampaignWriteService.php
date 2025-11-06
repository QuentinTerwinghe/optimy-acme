<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Models\Campaign;
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

            $campaign = Campaign::create($data);

            DB::commit();

            Log::info('Campaign created successfully', [
                'campaign_id' => $campaign->id,
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
            $campaign->update($data);

            DB::commit();

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
