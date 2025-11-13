<?php

declare(strict_types=1);

namespace App\Services\Notifications\Handlers;

use App\Enums\Notification\NotificationType;
use App\Mail\Payment\PaymentSuccessfulMail;
use App\Models\Auth\User;
use App\Models\Payment\Payment;
use App\Services\Notifications\AbstractNotificationHandler;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Handler for payment success notifications.
 * Sends emails to payment initiators when a payment is successful.
 */
class PaymentSuccessHandler extends AbstractNotificationHandler
{
    /**
     * Get the notification type this handler supports.
     */
    public function getNotificationType(): NotificationType
    {
        return NotificationType::PAYMENT_SUCCESS;
    }

    /**
     * Validate the receiver and parameters before sending.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters
     * @return void
     * @throws \InvalidArgumentException If validation fails
     */
    protected function validate(User $receiver, array $parameters): void
    {
        parent::validate($receiver, $parameters);

        if (!isset($parameters['payment'])) {
            throw new \InvalidArgumentException('Missing required parameter: payment');
        }

        if (!$parameters['payment'] instanceof Payment) {
            throw new \InvalidArgumentException('Parameter "payment" must be an instance of Payment');
        }
    }

    /**
     * Send the payment success notification.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters Expected keys:
     *                                         - payment: Payment (required) - The successful payment
     * @return bool
     */
    protected function send(User $receiver, array $parameters): bool
    {
        try {
            /** @var Payment $payment */
            $payment = $parameters['payment'];

            Log::info('Payment success notification being sent', [
                'payment_id' => $payment->id,
                'receiver' => $receiver->email,
            ]);

            Mail::to($receiver->email)->send(
                new PaymentSuccessfulMail(
                    receiver: $receiver,
                    payment: $payment->toArray(),
                )
            );

            Log::info('Payment success notification sent');

            return true;
        } catch (\Exception $e) {
            $this->logError($receiver, $parameters, $e);
            return false;
        }
    }
}
