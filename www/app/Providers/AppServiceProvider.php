<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind AuthService interface to implementation
        $this->app->bind(
            \App\Contracts\AuthServiceInterface::class,
            \App\Services\AuthService::class
        );

        // Bind RateLimitService interface to implementation
        $this->app->bind(
            \App\Contracts\RateLimitServiceInterface::class,
            \App\Services\RateLimitService::class
        );

        // Bind PasswordResetService interface to implementation
        $this->app->bind(
            \App\Contracts\Auth\PasswordResetServiceInterface::class,
            \App\Services\Auth\PasswordResetService::class
        );

        // Bind CampaignService interface to implementation
        $this->app->bind(
            \App\Contracts\Services\CampaignServiceInterface::class,
            \App\Services\CampaignService::class
        );

        // Bind StatefulGuard for AuthService dependency injection
        $this->app->bind(
            \Illuminate\Contracts\Auth\StatefulGuard::class,
            function ($app) {
                return $app->make('auth')->guard();
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
