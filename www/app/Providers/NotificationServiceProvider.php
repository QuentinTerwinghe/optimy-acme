<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Notifications\NotificationRegistryInterface;
use App\Contracts\Notifications\NotificationServiceInterface;
use App\Services\Notifications\NotificationRegistry;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for notification services.
 * Handles binding of notification-related interfaces and registration of handlers.
 */
class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the registry as a singleton
        $this->app->singleton(NotificationRegistryInterface::class, NotificationRegistry::class);

        // Bind the notification service
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
    }

    /**
     * Bootstrap services.
     * Register all notification handlers here.
     */
    public function boot(): void
    {
        /** @var NotificationRegistryInterface $registry */
        $registry = $this->app->make(NotificationRegistryInterface::class);

        // Register your notification handlers here
        // Example:
        // $registry->register($this->app->make(IncentiveConfirmationHandler::class));
        // $registry->register($this->app->make(WelcomeEmailHandler::class));
        // $registry->register($this->app->make(PasswordResetHandler::class));
    }
}
