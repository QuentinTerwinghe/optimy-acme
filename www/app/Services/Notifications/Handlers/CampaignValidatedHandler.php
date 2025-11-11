<?php

declare(strict_types=1);

namespace App\Services\Notifications\Handlers;

use App\Enums\Notification\NotificationType;
use App\Mail\Campaign\CampaignValidatedMail;
use App\Mail\Campaign\CampaignWaitingForValidationMail;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Services\Notifications\AbstractNotificationHandler;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Handler for campaign validated notifications.
 * Sends emails to campaign managers when a campaign status changes to validated.
 */
class CampaignValidatedHandler extends AbstractNotificationHandler
{
    /**
     * Get the notification type this handler supports.
     */
    public function getNotificationType(): NotificationType
    {
        return NotificationType::CAMPAIGN_VALIDATED;
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

        if (!isset($parameters['campaign'])) {
            throw new \InvalidArgumentException('Missing required parameter: campaign');
        }

        if (!$parameters['campaign'] instanceof Campaign) {
            throw new \InvalidArgumentException('Parameter "campaign" must be an instance of Campaign');
        }
    }

    /**
     * Send the campaign validation notification.
     *
     * @param User $receiver
     * @param array<string, mixed> $parameters Expected keys:
     *                                         - campaign: Campaign (required) - The campaign validated
     * @return bool
     */
    protected function send(User $receiver, array $parameters): bool
    {
        try {
            /** @var Campaign $campaign */
            $campaign = $parameters['campaign'];

            Log::info('Notification being sent', [
                'campaign_id' => $campaign->id,
                'receiver' => $receiver->email,
            ]);

            Mail::to($receiver->email)->send(
                new CampaignValidatedMail(
                    receiver: $receiver,
                    campaign: $campaign->toArray(),
                )
            );

            Log::info('Notification sent');

            return true;
        } catch (\Exception $e) {
            $this->logError($receiver, $parameters, $e);
            return false;
        }
    }
}
