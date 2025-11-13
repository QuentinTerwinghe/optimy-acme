<?php

namespace App\Providers;

use App\Models\Campaign\Campaign;
use App\Models\Payment\Payment;
use App\Observers\Campaign\CampaignObserver;
use App\Policies\Campaign\CampaignPolicy;
use App\Policies\Donation\DonationPolicy;
use App\Policies\Payment\PaymentPolicy;
use Illuminate\Support\Facades\Gate;
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

        // Bind Campaign repository interfaces to implementations
        $this->app->bind(
            \App\Contracts\Campaign\CampaignRepositoryInterface::class,
            \App\Repositories\Campaign\CampaignRepository::class
        );

        // Bind focused repository interfaces to same implementation (follows ISP)
        $this->app->bind(
            \App\Contracts\Campaign\CampaignReadRepositoryInterface::class,
            \App\Repositories\Campaign\CampaignRepository::class
        );

        $this->app->bind(
            \App\Contracts\Campaign\CampaignWriteRepositoryInterface::class,
            \App\Repositories\Campaign\CampaignRepository::class
        );

        $this->app->bind(
            \App\Contracts\Campaign\CampaignAggregateRepositoryInterface::class,
            \App\Repositories\Campaign\CampaignRepository::class
        );

        // Bind Campaign status validator interface to implementation
        $this->app->bind(
            \App\Contracts\Campaign\CampaignStatusValidatorInterface::class,
            \App\Services\Campaign\CampaignStatusValidator::class
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

        // Bind focused service interfaces to same implementation (follows ISP)
        $this->app->bind(
            \App\Contracts\Campaign\CampaignFinderInterface::class,
            \App\Services\Campaign\CampaignQueryService::class
        );

        $this->app->bind(
            \App\Contracts\Campaign\CampaignFilterInterface::class,
            \App\Services\Campaign\CampaignQueryService::class
        );

        $this->app->bind(
            \App\Contracts\Campaign\CampaignStatisticsInterface::class,
            \App\Services\Campaign\CampaignQueryService::class
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

        // Bind Donation services interfaces to implementations
        $this->app->bind(
            \App\Contracts\Donation\DonationServiceInterface::class,
            \App\Services\Donation\DonationService::class
        );

        // Bind Donation repository interface to implementation
        $this->app->bind(
            \App\Contracts\Donation\DonationRepositoryInterface::class,
            \App\Repositories\Donation\DonationRepository::class
        );

        // Bind Payment Gateway Registry interface to implementation (follows DIP)
        $this->app->bind(
            \App\Contracts\Payment\PaymentGatewayRegistryInterface::class,
            \App\Services\Payment\PaymentGatewayRegistry::class
        );

        // Bind Payment services interfaces to implementations
        $this->app->bind(
            \App\Contracts\Payment\PaymentProcessServiceInterface::class,
            \App\Services\Payment\PaymentProcessService::class
        );

        // Register PaymentCallbackService as singleton to maintain handler registrations
        $this->app->singleton(
            \App\Contracts\Payment\PaymentCallbackServiceInterface::class,
            function ($app) {
                $service = new \App\Services\Payment\PaymentCallbackService();

                // Register all payment callback handlers
                $service->registerHandler(
                    new \App\Services\Payment\CallbackHandlers\FakePaymentCallbackHandler()
                );

                // Future handlers can be registered here:
                // $service->registerHandler(new \App\Services\Payment\CallbackHandlers\PayPalCallbackHandler());
                // $service->registerHandler(new \App\Services\Payment\CallbackHandlers\StripeCallbackHandler());

                return $service;
            }
        );

        // Bind concrete class to interface for backwards compatibility
        $this->app->alias(
            \App\Contracts\Payment\PaymentCallbackServiceInterface::class,
            \App\Services\Payment\PaymentCallbackService::class
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
        // Register Campaign Policy
        Gate::policy(Campaign::class, CampaignPolicy::class);

        // Register Donation Policy abilities
        Gate::define('donate', [DonationPolicy::class, 'donate']);

        // Register Payment Policy (includes both access and view methods)
        Gate::policy(Payment::class, PaymentPolicy::class);

        // Register Campaign Observer
        Campaign::observe(CampaignObserver::class);
    }
}
