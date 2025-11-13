<?php

namespace App\Services\Payment;

use App\Contracts\Donation\DonationServiceInterface;
use App\Contracts\Payment\PaymentGatewayRegistryInterface;
use App\Contracts\Payment\PaymentServiceInterface;
use App\Contracts\Payment\ProcessPaymentDTOInterface;
use App\DTOs\Payment\RefundPaymentDTO;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Exceptions\Payment\PaymentProcessingException;
use App\Exceptions\Payment\PaymentRefundException;
use App\Exceptions\Payment\UnsupportedPaymentMethodException;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling payment operations.
 * Uses Strategy pattern via PaymentGatewayRegistry to delegate to appropriate gateway.
 * Follows Single Responsibility Principle (SRP) - handles ONLY payment processing.
 * Follows Dependency Inversion Principle (DIP) - depends on interfaces.
 * Donation status updates delegated to DonationService (extracted for better SRP).
 */
class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private PaymentGatewayRegistryInterface $gatewayRegistry,
        private DonationServiceInterface $donationService
    ) {}

    /**
     * Create a payment record for a donation.
     *
     * @param Donation $donation
     * @param PaymentMethodEnum $paymentMethod
     * @param array<string, mixed> $metadata
     * @return Payment
     */
    public function createPayment(
        Donation $donation,
        PaymentMethodEnum $paymentMethod,
        array $metadata = []
    ): Payment {
        return Payment::create([
            'donation_id' => $donation->id,
            'payment_method' => $paymentMethod,
            'status' => PaymentStatusEnum::PENDING,
            'amount' => $donation->amount,
            'currency' => 'USD', // Could be configurable or from donation
            'metadata' => $metadata,
            'initiated_at' => now(),
        ]);
    }

    /**
     * Process a payment through the appropriate gateway.
     *
     * @param Payment $payment
     * @param ProcessPaymentDTOInterface $paymentDTO
     * @return Payment
     * @throws PaymentProcessingException
     * @throws UnsupportedPaymentMethodException
     */
    public function processPayment(Payment $payment, ProcessPaymentDTOInterface $paymentDTO): Payment
    {
        try {
            // Start a database transaction
            DB::beginTransaction();

            // Validate payment can be processed
            if ($payment->status->isTerminal()) {
                throw PaymentProcessingException::forPayment(
                    (string) $payment->id,
                    "Payment is in a terminal state: {$payment->status->value}"
                );
            }

            // Get the appropriate gateway using Strategy pattern
            $gateway = $this->gatewayRegistry->getGateway($payment->payment_method);

            // Log payment initiation
            Log::info('Processing payment', [
                'payment_id' => $payment->id,
                'donation_id' => $payment->donation_id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method->value,
                'gateway' => $gateway->getName(),
            ]);

            // Process through the gateway
            $processedPayment = $gateway->processPayment($payment, $paymentDTO);

            // If payment is completed, update donation status
            if ($processedPayment->isCompleted()) {
                $this->handleSuccessfulPayment($processedPayment);
            }

            DB::commit();

            Log::info('Payment processed successfully', [
                'payment_id' => $processedPayment->id,
                'status' => $processedPayment->status->value,
                'transaction_id' => $processedPayment->transaction_id,
            ]);

            return $processedPayment;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Payment processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Refund a payment.
     *
     * @param Payment $payment
     * @param float|null $amount
     * @return Payment
     * @throws PaymentRefundException
     * @throws UnsupportedPaymentMethodException
     */
    public function refundPayment(Payment $payment, ?float $amount = null): Payment
    {
        try {
            DB::beginTransaction();

            // Create DTO for refund
            $dto = new RefundPaymentDTO(
                amount: $amount,
                reason: null,
                metadata: []
            );

            // Get the appropriate gateway
            $gateway = $this->gatewayRegistry->getGateway($payment->payment_method);

            Log::info('Processing refund', [
                'payment_id' => $payment->id,
                'refund_amount' => $amount ?? $payment->amount,
                'gateway' => $gateway->getName(),
            ]);

            // Process refund through the gateway
            $refundedPayment = $gateway->refundPayment($payment, $dto);

            // Update donation status if needed
            $this->handleRefundedPayment($refundedPayment);

            DB::commit();

            Log::info('Refund processed successfully', [
                'payment_id' => $refundedPayment->id,
                'status' => $refundedPayment->status->value,
            ]);

            return $refundedPayment;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Refund processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify the status of a payment with the gateway.
     *
     * @param Payment $payment
     * @return Payment
     * @throws UnsupportedPaymentMethodException
     */
    public function verifyPaymentStatus(Payment $payment): Payment
    {
        $gateway = $this->gatewayRegistry->getGateway($payment->payment_method);

        return $gateway->verifyPaymentStatus($payment);
    }

    /**
     * Get all available payment methods.
     *
     * @return array<PaymentMethodEnum>
     */
    public function getAvailablePaymentMethods(): array
    {
        return $this->gatewayRegistry->getAvailableMethods();
    }

    /**
     * Get enabled payment methods with their metadata for display.
     *
     * @return array<array{value: string, label: string, isTest: bool}>
     */
    public function getEnabledPaymentMethodsForDisplay(): array
    {
        $availableMethods = $this->getAvailablePaymentMethods();

        return array_map(
            fn (PaymentMethodEnum $method) => [
                'value' => $method->value,
                'label' => $method->label(),
                'isTest' => $method->isTest(),
            ],
            $availableMethods
        );
    }

    /**
     * Handle successful payment by updating related donation.
     * Delegates to DonationService following Single Responsibility Principle (SRP).
     *
     * @param Payment $payment
     * @return void
     */
    private function handleSuccessfulPayment(Payment $payment): void
    {
        $donation = $payment->donation;

        if ($donation) {
            // Delegate donation status update to DonationService
            $this->donationService->markDonationAsSuccessful($donation, $payment);
        }
    }

    /**
     * Handle refunded payment by updating related donation.
     * Delegates to DonationService following Single Responsibility Principle (SRP).
     *
     * @param Payment $payment
     * @return void
     */
    private function handleRefundedPayment(Payment $payment): void
    {
        $donation = $payment->donation;

        if ($donation) {
            // Delegate donation status update to DonationService
            $this->donationService->markDonationAsFailed($donation, $payment, 'Payment was refunded');
        }
    }

    /**
     * Get payment statistics for a donation.
     *
     * @param Donation $donation
     * @return array<string, mixed>
     */
    public function getPaymentStatistics(Donation $donation): array
    {
        $payments = $donation->payments;

        return [
            'total_payments' => $payments->count(),
            'successful_payments' => $payments->where('status', PaymentStatusEnum::COMPLETED)->count(),
            'failed_payments' => $payments->where('status', PaymentStatusEnum::FAILED)->count(),
            'pending_payments' => $payments->where('status', PaymentStatusEnum::PENDING)->count(),
            'refunded_payments' => $payments->where('status', PaymentStatusEnum::REFUNDED)->count(),
            'total_amount_paid' => $payments->where('status', PaymentStatusEnum::COMPLETED)->sum('amount'),
            'total_amount_refunded' => $payments->where('status', PaymentStatusEnum::REFUNDED)->sum('amount'),
        ];
    }
}
