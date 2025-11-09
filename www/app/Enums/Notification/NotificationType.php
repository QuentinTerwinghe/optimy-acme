<?php

declare(strict_types=1);

namespace App\Enums\Notification;

enum NotificationType: string
{
    case FORGOT_PASSWORD = 'forgot_password';
}
