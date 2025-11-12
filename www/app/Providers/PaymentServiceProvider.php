<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Payment\PaymentPreparationServiceInterface;
use App\Contracts\Payment\PaymentServiceInterface;
use App\Enums\Payment\PaymentMethodEnum;
use App\Services\Payment\Gateways\FakePaymentGateway;
use App\Services\Payment\PaymentGatewayRegistry;
use App\Services\Payment\PaymentPreparationService;
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

        // Bind the payment preparation service interface to implementation as a singleton
        $this->app->singleton(
            PaymentPreparationServiceInterface::class,
            PaymentPreparationService::class
        );
    }

    /**
     * Bootstrap services.
     * Register all payment gateways and handlers here.
     */
    public function boot(): void
    {
        /** @var PaymentGatewayRegistry $registry */
        $registry = $this->app->make(PaymentGatewayRegistry::class);

        // Get the payment preparation service
        /** @var PaymentPreparationServiceInterface $preparationService */
        $preparationService = $this->app->make(PaymentPreparationServiceInterface::class);

        // Get gateway instances
        $fakeGateway = $this->app->make(FakePaymentGateway::class);

        // Register payment gateways
        $registry->register($fakeGateway);

        // Register payment method handlers
        $preparationService->registerHandler(PaymentMethodEnum::FAKE->value, $fakeGateway);

        // Future gateways can be registered here:
        // $paypalGateway = $this->app->make(PayPalGateway::class);
        // $registry->register($paypalGateway);
        // $preparationService->registerHandler(PaymentMethodEnum::PAYPAL->value, $paypalGateway);

        // $stripeGateway = $this->app->make(StripeGateway::class);
        // $registry->register($stripeGateway);
        // $preparationService->registerHandler(PaymentMethodEnum::STRIPE->value, $stripeGateway);
    }
}
