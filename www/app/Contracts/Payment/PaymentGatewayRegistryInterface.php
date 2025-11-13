<?php

declare(strict_types=1);

namespace App\Contracts\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Exceptions\Payment\UnsupportedPaymentMethodException;

/**
 * Interface for payment gateway registry.
 * Follows Dependency Inversion Principle (DIP) - services depend on abstractions.
 */
interface PaymentGatewayRegistryInterface
{
    /**
     * Register a payment gateway.
     *
     * @param PaymentGatewayInterface $gateway Gateway implementation
     * @return void
     */
    public function register(PaymentGatewayInterface $gateway): void;

    /**
     * Get a payment gateway for the given method.
     *
     * @param PaymentMethodEnum $method
     * @return PaymentGatewayInterface
     * @throws UnsupportedPaymentMethodException
     */
    public function getGateway(PaymentMethodEnum $method): PaymentGatewayInterface;

    /**
     * Check if a gateway is registered for the given method.
     *
     * @param PaymentMethodEnum $method
     * @return bool
     */
    public function hasGateway(PaymentMethodEnum $method): bool;

    /**
     * Get all registered payment methods.
     *
     * @return array<string>
     */
    public function getRegisteredMethods(): array;

    /**
     * Get all available (enabled and registered) payment methods.
     *
     * @return array<PaymentMethodEnum>
     */
    public function getAvailableMethods(): array;

    /**
     * Get all registered gateways.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public function getAllGateways(): array;
}
