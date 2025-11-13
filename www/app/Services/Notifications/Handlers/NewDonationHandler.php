<?php

declare(strict_types=1);

namespace App\Services\Notifications\Handlers;

use App\Enums\Notification\NotificationType;
use App\Mail\Donation\NewDonationMail;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Donation\Donation;
use App\Services\Notifications\AbstractNotificationHandler;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Handler for new donation notifications.
 * Sends emails to campaign creators when a new donation is made.
 */
class NewDonationHandler extends AbstractNotificationHandler
{
    /**
     * Get the notification type this handler supports.
     */
    public function getNotificationType(): NotificationType
    {
        return NotificationType::NEW_DONATION;
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

        if (!isset($parameters['donation'])) {
            throw new \InvalidArgumentException('Missing required parameter: donation');
        }

        if (!$parameters['donation'] instanceof Donation) {
            throw new \InvalidArgumentException('Parameter "donation" must be an instance of Donation');
        }

        if (!isset($parameters['campaign'])) {
            throw new \InvalidArgumentException('Missing required parameter: campaign');
        }

        if (!$parameters['campaign'] instanceof Campaign) {
            throw new \InvalidArgumentException('Parameter "campaign" must be an instance of Campaign');
        }
    }

    /**
     * Send the new donation notification.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters Expected keys:
     *                                         - donation: Donation (required) - The new donation
     *                                         - campaign: Campaign (required) - The campaign receiving the donation
     * @return bool
     */
    protected function send(User $receiver, array $parameters): bool
    {
        try {
            /** @var Donation $donation */
            $donation = $parameters['donation'];
            /** @var Campaign $campaign */
            $campaign = $parameters['campaign'];

            Log::info('New donation notification being sent', [
                'donation_id' => $donation->id,
                'campaign_id' => $campaign->id,
                'receiver' => $receiver->email,
            ]);

            Mail::to($receiver->email)->send(
                new NewDonationMail(
                    receiver: $receiver,
                    campaign: $campaign->toArray(),
                    donation: $donation->toArray(),
                )
            );

            Log::info('New donation notification sent');

            return true;
        } catch (\Exception $e) {
            $this->logError($receiver, $parameters, $e);
            return false;
        }
    }
}
