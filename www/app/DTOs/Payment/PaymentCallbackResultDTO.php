<?php

namespace App\DTOs\Payment;

use App\Enums\Payment\PaymentStatusEnum;

/**
 * DTO representing the result of a payment callback processing.
 * This standardizes the data returned by different payment gateway handlers.
 */
readonly class PaymentCallbackResultDTO
{
    /**
     * @param PaymentStatusEnum $status The final status of the payment
     * @param string|null $transactionId The transaction ID from the gateway (if successful)
     * @param array<string, mixed>|null $gatewayResponse Additional response data from the gateway
     * @param string|null $errorMessage Error message if payment failed
     * @param string|null $errorCode Error code if payment failed
     * @param string $redirectRoute The route name to redirect to after processing
     * @param array<string, mixed> $redirectParams Additional parameters for the redirect route
     */
    public function __construct(
        public PaymentStatusEnum $status,
        public ?string $transactionId = null,
        public ?array $gatewayResponse = null,
        public ?string $errorMessage = null,
        public ?string $errorCode = null,
        public string $redirectRoute = 'dashboard',
        public array $redirectParams = [],
    ) {
    }

    /**
     * Check if the callback indicates a successful payment.
     */
    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatusEnum::COMPLETED;
    }

    /**
     * Check if the callback indicates a failed payment.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatusEnum::FAILED;
    }

    /**
     * Create a successful callback result.
     *
     * @param string $transactionId
     * @param array<string, mixed>|null $gatewayResponse
     * @param string $redirectRoute
     * @param array<string, mixed> $redirectParams
     * @return self
     */
    public static function success(
        string $transactionId,
        ?array $gatewayResponse = null,
        string $redirectRoute = 'dashboard',
        array $redirectParams = []
    ): self {
        return new self(
            status: PaymentStatusEnum::COMPLETED,
            transactionId: $transactionId,
            gatewayResponse: $gatewayResponse,
            redirectRoute: $redirectRoute,
            redirectParams: $redirectParams
        );
    }

    /**
     * Create a failed callback result.
     *
     * @param string $errorMessage
     * @param string|null $errorCode
     * @param array<string, mixed>|null $gatewayResponse
     * @param string $redirectRoute
     * @param array<string, mixed> $redirectParams
     * @return self
     */
    public static function failed(
        string $errorMessage,
        ?string $errorCode = null,
        ?array $gatewayResponse = null,
        string $redirectRoute = 'dashboard',
        array $redirectParams = []
    ): self {
        return new self(
            status: PaymentStatusEnum::FAILED,
            errorMessage: $errorMessage,
            errorCode: $errorCode,
            gatewayResponse: $gatewayResponse,
            redirectRoute: $redirectRoute,
            redirectParams: $redirectParams
        );
    }
}
