<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\Campaign\CampaignPermissions;
use App\Enums\Notification\NotificationType;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to send campaign waiting for validation notifications via RabbitMQ.
 * This job is dispatched to the queue and processed asynchronously.
 */
class SendCampaignWaitingForValidationNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     *
     * @param string $campaignId Campaign ID (UUID as string)
     * @param string $creatorId Creator user ID
     */
    public function __construct(
        public readonly string $campaignId,
        public readonly string $creatorId
    ) {
    }

    /**
     * Execute the job.
     * Retrieves all campaign managers and sends notification to each one.
     *
     * @param NotificationServiceInterface $notificationService
     * @return void
     */
    public function handle(NotificationServiceInterface $notificationService): void
    {
        try {
            // Find the campaign
            $campaign = Campaign::find($this->campaignId);

            if (!$campaign) {
                Log::error('Campaign not found for notification job', [
                    'campaign_id' => $this->campaignId,
                ]);
                return;
            }

            // Find the creator
            $creator = User::find($this->creatorId);

            if (!$creator) {
                Log::warning('Creator not found for campaign validation notification', [
                    'campaign_id' => $this->campaignId,
                    'creator_id' => $this->creatorId,
                ]);
                return;
            }

            // Get all users with MANAGE_ALL_CAMPAIGNS permission
            // This includes both campaign_manager role and admin role (which has wildcard permission)
            $campaignManagers = User::permission(CampaignPermissions::MANAGE_ALL_CAMPAIGNS->value)->get();

            Log::info('Processing campaign validation notification job', [
                'campaign_id' => $this->campaignId,
                'campaign_title' => $campaign->title,
                'creator_id' => $this->creatorId,
                'managers_count' => $campaignManagers->count(),
            ]);

            // Send notification to each campaign manager
            foreach ($campaignManagers as $manager) {
                try {
                    $notificationService->send(
                        $manager,
                        NotificationType::CAMPAIGN_WAITING_FOR_VALIDATION,
                        [
                            'campaign' => $campaign,
                            'creator' => $creator,
                        ]
                    );

                    Log::info('Campaign validation notification sent', [
                        'campaign_id' => $this->campaignId,
                        'manager_id' => $manager->id,
                        'manager_email' => $manager->email,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send campaign validation notification to manager', [
                        'campaign_id' => $this->campaignId,
                        'manager_id' => $manager->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue sending to other managers even if one fails
                }
            }

            Log::info('Campaign validation notification job completed', [
                'campaign_id' => $this->campaignId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process campaign validation notification job', [
                'campaign_id' => $this->campaignId,
                'creator_id' => $this->creatorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger job failure and retry mechanism
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Campaign validation notification job failed permanently', [
            'campaign_id' => $this->campaignId,
            'creator_id' => $this->creatorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
