<?php

declare(strict_types=1);

namespace App\Jobs\Notification;

use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\Notification\NotificationType;
use App\Models\Donation\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to send new donation notifications via RabbitMQ.
 * This job is dispatched to the queue and processed asynchronously.
 * Notifies the campaign creator when a new donation is received.
 */
class SendNewDonationNotificationJob implements ShouldQueue
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
     * @param string $donationId Donation ID (UUID as string)
     */
    public function __construct(
        public readonly string $donationId,
    ) {}

    /**
     * Execute the job.
     * Sends new donation notification to the campaign creator.
     *
     * @param NotificationServiceInterface $notificationService
     * @return void
     */
    public function handle(NotificationServiceInterface $notificationService): void
    {
        try {
            Log::info('Processing new donation notification job', [
                'donation_id' => $this->donationId,
            ]);

            // Find the donation with campaign and creator relationships
            $donation = Donation::with(['campaign', 'campaign.creator'])->find($this->donationId);

            if (empty($donation)) {
                Log::error('Donation not found for notification job', [
                    'donation_id' => $this->donationId,
                ]);
                return;
            }

            $campaign = $donation->campaign;
            $campaignCreator = $campaign->creator;

            if (empty($campaignCreator)) {
                Log::error('Campaign creator not found for donation notification job', [
                    'donation_id' => $this->donationId,
                    'campaign_id' => $campaign->id,
                ]);
                return;
            }

            $notificationService->send(
                $campaignCreator,
                NotificationType::NEW_DONATION,
                [
                    'donation' => $donation,
                    'campaign' => $campaign,
                ]
            );

            Log::info('New donation notification job completed', [
                'donation_id' => $this->donationId,
                'campaign_id' => $campaign->id,
                'campaign_creator_id' => $campaignCreator->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process new donation notification job', [
                'donation_id' => $this->donationId,
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
        Log::error('New donation notification job failed permanently', [
            'donation_id' => $this->donationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
