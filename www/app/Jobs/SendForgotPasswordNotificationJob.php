<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\NotificationType;
use App\Models\Auth\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to send forgot password notification via RabbitMQ.
 * This job is dispatched to the queue and processed asynchronously.
 */
class SendForgotPasswordNotificationJob implements ShouldQueue
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
     * @param int $userId User ID who will receive the notification
     * @param string $token Password reset token (plain, not hashed)
     * @param int $expirationMinutes Token expiration in minutes
     */
    public function __construct(
        private readonly int $userId,
        private readonly string $token,
        private readonly int $expirationMinutes
    ) {
    }

    /**
     * Execute the job.
     *
     * @param NotificationServiceInterface $notificationService
     * @return void
     */
    public function handle(NotificationServiceInterface $notificationService): void
    {
        try {
            $user = User::findOrFail($this->userId);

            Log::info('Processing forgot password notification job', [
                'user_id' => $this->userId,
                'email' => $user->email,
            ]);

            $notificationService->send(
                $user,
                NotificationType::FORGOT_PASSWORD,
                [
                    'token' => $this->token,
                    'expiration_minutes' => $this->expirationMinutes,
                ]
            );

            Log::info('Forgot password notification job completed', [
                'user_id' => $this->userId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process forgot password notification job', [
                'user_id' => $this->userId,
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
        Log::error('Forgot password notification job failed permanently', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
