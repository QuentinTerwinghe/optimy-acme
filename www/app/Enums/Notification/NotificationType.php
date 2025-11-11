<?php

declare(strict_types=1);

namespace App\Enums\Notification;

enum NotificationType: string
{
    case FORGOT_PASSWORD = 'forgot_password';
    case CAMPAIGN_WAITING_FOR_VALIDATION = 'campaign_waiting_for_validation';
    case CAMPAIGN_VALIDATED = 'campaign_validated';
    case CAMPAIGN_REJECTED = 'campaign_rejected';
}
