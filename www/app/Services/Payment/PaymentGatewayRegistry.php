<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Contracts\Payment\PaymentGatewayRegistryInterface;
use App\Enums\Payment\PaymentMethodEnum;
use App\Exceptions\Payment\UnsupportedPaymentMethodException;

/**
 * Registry for payment gateway implementations.
 * Follows the Registry pattern similar to NotificationChannelRegistry.
 * Implements PaymentGatewayRegistryInterface for Dependency Inversion Principle (DIP).
 */
class PaymentGatewayRegistry implements PaymentGatewayRegistryInterface
{
    /**
     * Registered payment gateways.
     *
     * @var array<string, PaymentGatewayInterface>
     */
    private array $gateways = [];

    /**
     * Register a payment gateway.
     *
     * @param PaymentGatewayInterface $gateway Gateway implementation
     * @return void
     */
    public function register(PaymentGatewayInterface $gateway): void
    {
        $method = $gateway->getPaymentMethod();
        $this->gateways[$method->value] = $gateway;
    }

    /**
     * Get a payment gateway for the given method.
     *
     * @param PaymentMethodEnum $method
     * @return PaymentGatewayInterface
     * @throws UnsupportedPaymentMethodException
     */
    public function getGateway(PaymentMethodEnum $method): PaymentGatewayInterface
    {
        $methodValue = $method->value;

        if (!isset($this->gateways[$methodValue])) {
            throw UnsupportedPaymentMethodException::noGatewayAvailable($methodValue);
        }

        return $this->gateways[$methodValue];
    }

    /**
     * Check if a gateway is registered for the given method.
     *
     * @param PaymentMethodEnum $method
     * @return bool
     */
    public function hasGateway(PaymentMethodEnum $method): bool
    {
        return isset($this->gateways[$method->value]);
    }

    /**
     * Get all registered payment methods.
     *
     * @return array<string>
     */
    public function getRegisteredMethods(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Get all available (enabled and registered) payment methods.
     *
     * @return array<PaymentMethodEnum>
     */
    public function getAvailableMethods(): array
    {
        return array_filter(
            PaymentMethodEnum::cases(),
            fn (PaymentMethodEnum $method) => $method->isEnabled() && $this->hasGateway($method)
        );
    }

    /**
     * Get all registered gateways.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public function getAllGateways(): array
    {
        return $this->gateways;
    }
}
