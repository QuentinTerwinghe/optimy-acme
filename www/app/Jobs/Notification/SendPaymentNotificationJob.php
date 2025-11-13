<?php

declare(strict_types=1);

namespace App\Jobs\Notification;

use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\Notification\NotificationType;
use App\Models\Auth\User;
use App\Models\Payment\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to send payment success/failure notifications via RabbitMQ.
 * This job is dispatched to the queue and processed asynchronously.
 */
class SendPaymentNotificationJob implements ShouldQueue
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
     * @param string $userId User ID (UUID as string) - The payment initiator
     * @param string $paymentId Payment ID (UUID as string)
     * @param NotificationType $notificationType The type of notification (success or failure)
     */
    public function __construct(
        private readonly string $userId,
        public readonly string $paymentId,
        private readonly NotificationType $notificationType,
    ) {}

    /**
     * Execute the job.
     * Sends payment notification to the payment initiator.
     *
     * @param NotificationServiceInterface $notificationService
     * @return void
     */
    public function handle(NotificationServiceInterface $notificationService): void
    {
        try {
            $user = User::findOrFail($this->userId);

            Log::info('Processing payment notification job', [
                'user_id' => $this->userId,
                'payment_id' => $this->paymentId,
                'notification_type' => $this->notificationType->value,
            ]);

            // Find the payment
            $payment = Payment::find($this->paymentId);

            if (!$payment) {
                Log::error('Payment not found for notification job', [
                    'payment_id' => $this->paymentId,
                ]);
                return;
            }

            $notificationService->send(
                $user,
                $this->notificationType,
                [
                    'payment' => $payment,
                ]
            );

            Log::info('Payment notification job completed', [
                'payment_id' => $this->paymentId,
                'notification_type' => $this->notificationType->value,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process payment notification job', [
                'payment_id' => $this->paymentId,
                'notification_type' => $this->notificationType->value,
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
        Log::error('Payment notification job failed permanently', [
            'payment_id' => $this->paymentId,
            'notification_type' => $this->notificationType->value,
            'error' => $exception->getMessage(),
        ]);
    }
}
