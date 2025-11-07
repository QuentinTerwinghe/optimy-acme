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
            \App\Contracts\Auth\AuthServiceInterface::class,
            \App\Services\Auth\AuthService::class
        );

        // Bind RateLimitService interface to implementation
        $this->app->bind(
            \App\Contracts\Auth\RateLimitServiceInterface::class,
            \App\Services\Auth\RateLimitService::class
        );

        // Bind PasswordResetService interface to implementation
        $this->app->bind(
            \App\Contracts\Auth\PasswordResetServiceInterface::class,
            \App\Services\Auth\PasswordResetService::class
        );

        // Bind Campaign services interfaces to implementations
        $this->app->bind(
            \App\Contracts\Campaign\CampaignQueryServiceInterface::class,
            \App\Services\Campaign\CampaignQueryService::class
        );

        $this->app->bind(
            \App\Contracts\Campaign\CampaignWriteServiceInterface::class,
            \App\Services\Campaign\CampaignWriteService::class
        );

        // Bind Tag services interfaces to implementations
        $this->app->bind(
            \App\Contracts\Tag\TagQueryServiceInterface::class,
            \App\Services\Tag\TagQueryService::class
        );

        $this->app->bind(
            \App\Contracts\Tag\TagWriteServiceInterface::class,
            \App\Services\Tag\TagWriteService::class
        );

        // Bind Category services interfaces to implementations
        $this->app->bind(
            \App\Contracts\Category\CategoryQueryServiceInterface::class,
            \App\Services\Category\CategoryQueryService::class
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
