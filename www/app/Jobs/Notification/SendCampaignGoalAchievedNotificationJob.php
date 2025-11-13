<?php

declare(strict_types=1);

namespace App\Jobs\Notification;

use App\Contracts\Notifications\NotificationServiceInterface;
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
 * Job to send campaign goal achieved notifications via RabbitMQ.
 * This job is dispatched to the queue and processed asynchronously.
 * Notifies the campaign creator when their campaign goal is achieved.
 */
class SendCampaignGoalAchievedNotificationJob implements ShouldQueue
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
     */
    public function __construct(
        public readonly string $campaignId,
    ) {}

    /**
     * Execute the job.
     * Sends campaign goal achieved notification to the campaign creator.
     *
     * @param NotificationServiceInterface $notificationService
     * @return void
     */
    public function handle(NotificationServiceInterface $notificationService): void
    {
        try {
            Log::info('Processing campaign goal achieved notification job', [
                'campaign_id' => $this->campaignId,
            ]);

            // Find the campaign with creator relationship
            $campaign = Campaign::with('creator')->find($this->campaignId);

            if (!$campaign) {
                Log::error('Campaign not found for goal achieved notification job', [
                    'campaign_id' => $this->campaignId,
                ]);
                return;
            }

            $campaignCreator = $campaign->creator;

            if (!$campaignCreator) {
                Log::error('Campaign creator not found for goal achieved notification job', [
                    'campaign_id' => $this->campaignId,
                ]);
                return;
            }

            $notificationService->send(
                $campaignCreator,
                NotificationType::CAMPAIGN_GOAL_ACHIEVED,
                [
                    'campaign' => $campaign,
                ]
            );

            Log::info('Campaign goal achieved notification job completed', [
                'campaign_id' => $this->campaignId,
                'campaign_creator_id' => $campaignCreator->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process campaign goal achieved notification job', [
                'campaign_id' => $this->campaignId,
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
        Log::error('Campaign goal achieved notification job failed permanently', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
        ]);
    }
}
