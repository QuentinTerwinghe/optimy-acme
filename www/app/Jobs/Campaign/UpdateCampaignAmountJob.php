<?php

declare(strict_types=1);

namespace App\Jobs\Campaign;

use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Jobs\Notification\SendCampaignGoalAchievedNotificationJob;
use App\Models\Campaign\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to update campaign amount based on successful donations via RabbitMQ.
 *
 * This job ensures that campaign amounts are updated sequentially and consistently
 * by recalculating the total from all successful donations rather than incrementing.
 * This prevents race conditions and amount drift.
 */
class UpdateCampaignAmountJob implements ShouldQueue
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
    public int $backoff = 5;

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
     * Recalculates the campaign's total amount based on all successful donations.
     * Also checks if the campaign goal is achieved and dispatches notification if so.
     *
     * @param CampaignWriteServiceInterface $campaignWriteService
     * @return void
     */
    public function handle(CampaignWriteServiceInterface $campaignWriteService): void
    {
        try {
            Log::info('Processing campaign amount update job', [
                'campaign_id' => $this->campaignId,
            ]);

            // Find the campaign
            $campaign = Campaign::find($this->campaignId);

            if (!$campaign) {
                Log::error('Campaign not found for amount update job', [
                    'campaign_id' => $this->campaignId,
                ]);
                return;
            }

            // Store the previous amount to check if goal was just achieved
            $previousAmount = (float) $campaign->current_amount;
            $goalAmount = (float) $campaign->goal_amount;
            $wasGoalAlreadyAchieved = $previousAmount >= $goalAmount;

            // Recalculate the total amount
            $success = $campaignWriteService->recalculateTotalAmount($campaign);

            if ($success) {
                // Refresh the campaign to get the updated amount
                $campaign->refresh();
                $newAmount = (float) $campaign->current_amount;

                Log::info('Campaign amount update job completed successfully', [
                    'campaign_id' => $this->campaignId,
                    'previous_amount' => $previousAmount,
                    'new_amount' => $newAmount,
                    'goal_amount' => $goalAmount,
                ]);

                // Check if goal was just achieved (wasn't achieved before, but is now)
                $isGoalNowAchieved = $newAmount >= $goalAmount;

                if (!$wasGoalAlreadyAchieved && $isGoalNowAchieved) {
                    // Goal was just achieved! Dispatch notification job
                    SendCampaignGoalAchievedNotificationJob::dispatch($this->campaignId);

                    Log::info('Campaign goal achieved! Notification job dispatched', [
                        'campaign_id' => $this->campaignId,
                        'goal_amount' => $goalAmount,
                        'achieved_amount' => $newAmount,
                    ]);
                }
            } else {
                throw new \Exception('Failed to recalculate campaign amount');
            }
        } catch (\Exception $e) {
            Log::error('Failed to process campaign amount update job', [
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
        Log::error('Campaign amount update job failed permanently', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
        ]);
    }
}
