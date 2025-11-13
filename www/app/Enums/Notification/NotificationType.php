<?php

declare(strict_types=1);

namespace App\Enums\Notification;

enum NotificationType: string
{
    case FORGOT_PASSWORD = 'forgot_password';
    case CAMPAIGN_WAITING_FOR_VALIDATION = 'campaign_waiting_for_validation';
    case CAMPAIGN_VALIDATED = 'campaign_validated';
    case CAMPAIGN_REJECTED = 'campaign_rejected';
    case PAYMENT_SUCCESS = 'payment_success';
    case PAYMENT_FAILURE = 'payment_failure';
    case NEW_DONATION = 'new_donation';
    case CAMPAIGN_GOAL_ACHIEVED = 'campaign_goal_achieved';
}
