<?php

declare(strict_types=1);

namespace App\Observers\Campaign;

use App\Enums\Campaign\CampaignStatus;
use App\Jobs\SendCampaignWaitingForValidationNotificationJob;
use App\Models\Campaign\Campaign;
use Illuminate\Support\Facades\Log;

/**
 * Observer for Campaign model.
 * Handles events like status changes and dispatches notification jobs.
 */
class CampaignObserver
{
    /**
     * Track campaigns that need notifications
     * Key is campaign ID, value is old status
     *
     * @var array<string, CampaignStatus>
     */
    private static array $pendingNotifications = [];

    /**
     * Handle the Campaign "created" event.
     * This is called after a new model is created.
     *
     * @param Campaign $campaign
     * @return void
     */
    public function created(Campaign $campaign): void
    {
        Log::info('Created campaign', [
            'campaign_id' => $campaign->id,
            'campaign_title' => $campaign->title,
        ]);

        // Check if the newly created campaign has status WAITING_FOR_VALIDATION
        if ($campaign->status === CampaignStatus::WAITING_FOR_VALIDATION) {
            $this->dispatchNotificationJob($campaign);
        }
    }

    /**
     * Handle the Campaign "updating" event.
     * This is called before the model is saved, allowing us to detect changes.
     *
     * @param Campaign $campaign
     * @return void
     */
    public function updating(Campaign $campaign): void
    {
        // Check if status is being changed to WAITING_FOR_VALIDATION
        if ($campaign->isDirty('status')) {
            // getOriginal() with casts returns the enum instance, not the raw value
            $oldStatus = $campaign->getOriginal('status');
            $newStatus = $campaign->status;

            // Only trigger notification when transitioning from DRAFT to WAITING_FOR_VALIDATION
            // This prevents duplicate notifications if status is updated again
            if (
                $oldStatus === CampaignStatus::DRAFT &&
                $newStatus === CampaignStatus::WAITING_FOR_VALIDATION
            ) {
                // Mark this campaign for notification after save
                self::$pendingNotifications[$campaign->id] = $oldStatus;
            }
        }
    }

    /**
     * Handle the Campaign "updated" event.
     * This is called after the model is saved.
     *
     * @param Campaign $campaign
     * @return void
     */
    public function updated(Campaign $campaign): void
    {
        Log::info('Update campaign', [
            'campaign_id' => $campaign->id,
            'campaign_title' => $campaign->title,
        ]);

        // Check if this campaign needs notification
        if (isset(self::$pendingNotifications[$campaign->id])) {
            unset(self::$pendingNotifications[$campaign->id]);
            $this->dispatchNotificationJob($campaign);
        }
    }

    /**
     * Dispatch notification job for campaign waiting for validation.
     * The job will retrieve all campaign managers and send notifications to each one.
     *
     * @param Campaign $campaign
     * @return void
     */
    private function dispatchNotificationJob(Campaign $campaign): void
    {
        try {
            // Get the creator of the campaign
            $creator = $campaign->creator;

            if (!$creator) {
                Log::warning('Cannot dispatch campaign validation notification: creator not found', [
                    'campaign_id' => $campaign->id,
                ]);
                return;
            }

            Log::info('Dispatching campaign validation notification job', [
                'campaign_id' => $campaign->id,
                'campaign_title' => $campaign->title,
                'creator_id' => $creator->id,
            ]);

            // Dispatch job to queue for async processing
            SendCampaignWaitingForValidationNotificationJob::dispatch(
                $campaign->id,
                $creator->id
            );

            Log::info('Campaign validation notification job dispatched', [
                'campaign_id' => $campaign->id,
                'creator_id' => $creator->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch campaign validation notification job', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
