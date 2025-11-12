<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Payment\PaymentServiceInterface;
use App\Services\Payment\Gateways\FakePaymentGateway;
use App\Services\Payment\PaymentGatewayRegistry;
use App\Services\Payment\PaymentService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for payment services.
 * Handles binding of payment-related interfaces and registration of gateways.
 */
class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the gateway registry as a singleton
        $this->app->singleton(PaymentGatewayRegistry::class, PaymentGatewayRegistry::class);

        // Bind the payment service interface to implementation
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);
    }

    /**
     * Bootstrap services.
     * Register all payment gateways here.
     */
    public function boot(): void
    {
        /** @var PaymentGatewayRegistry $registry */
        $registry = $this->app->make(PaymentGatewayRegistry::class);

        // Register payment gateways
        $registry->register($this->app->make(FakePaymentGateway::class));

        // Future gateways can be registered here:
        // $registry->register($this->app->make(PayPalGateway::class));
        // $registry->register($this->app->make(StripeGateway::class));
    }
}
