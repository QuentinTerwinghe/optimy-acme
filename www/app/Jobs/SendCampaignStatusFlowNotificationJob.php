<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\Campaign\CampaignPermissions;
use App\Enums\Campaign\CampaignStatus;
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
 * Job to send campaign validated/rejected notifications via RabbitMQ.
 * This job is dispatched to the queue and processed asynchronously.
 */
class SendCampaignStatusFlowNotificationJob implements ShouldQueue
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
     * @param string $userId
     * @param string $campaignId Campaign ID (UUID as string)
     */
    public function __construct(
        private readonly string $userId,
        public readonly string $campaignId,
    ) {}

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
            $user = User::findOrFail($this->userId);

            Log::info('Processing campaign status flow notification job', [
                'user_id' => $this->userId,
                'email' => $user->email,
            ]);

            // Find the campaign
            $campaign = Campaign::find($this->campaignId);

            if (!$campaign) {
                Log::error('Campaign not found for notification job', [
                    'campaign_id' => $this->campaignId,
                ]);
                return;
            }

            /** @var CampaignStatus $status */
            $status = $campaign->status;

            switch ($status) {
                case CampaignStatus::REJECTED:
                    $notificationType = NotificationType::CAMPAIGN_REJECTED;
                    break;
                case CampaignStatus::ACTIVE:
                    $notificationType = NotificationType::CAMPAIGN_VALIDATED;
                    break;
                default:
                    Log::info('Invalid status for campaign status flow', [
                        'campaign_id' => $this->campaignId,
                        'status' => $status,
                    ]);
                    throw new \Exception($status . ' is not a valid status for campaign status flow');
            }

            $notificationService->send(
                $user,
                $notificationType,
                [
                    'campaign' => $campaign,
                ]
            );

            Log::info('Campaign validation notification job completed', [
                'campaign_id' => $this->campaignId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process campaign validation notification job', [
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
        Log::error('Campaign status flow notification job failed permanently', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
        ]);
    }
}
