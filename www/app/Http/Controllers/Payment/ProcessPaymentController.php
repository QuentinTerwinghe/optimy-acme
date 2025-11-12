<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Contracts\Payment\PaymentProcessServiceInterface;
use App\Exceptions\Payment\PaymentProcessingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitializePaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller for processing payment initialization.
 * Follows Single Responsibility - only handles HTTP concerns.
 * Delegates business logic to PaymentProcessService.
 */
class ProcessPaymentController extends Controller
{
    public function __construct(
        private PaymentProcessServiceInterface $paymentProcessService
    ) {}

    /**
     * Initialize a new payment by creating donation and payment records.
     *
     * @param InitializePaymentRequest $request
     * @return JsonResponse
     */
    public function initialize(InitializePaymentRequest $request): JsonResponse
    {
        try {
            // Extract validated data
            $campaignId = $request->validated('campaign_id');
            $amount = (float) $request->validated('amount');
            $paymentMethod = $request->getPaymentMethod();
            $metadata = $request->getMetadata();

            // Get authenticated user
            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }
            $userId = $user->id;

            // Initialize payment through service
            $result = $this->paymentProcessService->initializePayment(
                campaignId: $campaignId,
                userId: $userId,
                amount: $amount,
                paymentMethod: $paymentMethod,
                metadata: $metadata
            );

            // Return success response with both donation and payment
            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'donation' => [
                        'id' => $result['donation']->id,
                        'campaign_id' => $result['donation']->campaign_id,
                        'amount' => $result['donation']->amount,
                        'status' => $result['donation']->status->value,
                    ],
                    'payment' => [
                        'id' => $result['payment']->id,
                        'donation_id' => $result['payment']->donation_id,
                        'amount' => $result['payment']->amount,
                        'currency' => $result['payment']->currency,
                        'payment_method' => $result['payment']->payment_method->value,
                        'status' => $result['payment']->status->value,
                    ],
                ],
            ], 201);
        } catch (PaymentProcessingException $e) {
            Log::warning('Payment initialization failed - processing exception', [
                'user_id' => $request->user()?->id,
                'campaign_id' => $request->validated('campaign_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment initialization failed - unexpected error', [
                'user_id' => $request->user()?->id,
                'campaign_id' => $request->validated('campaign_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while initializing payment',
            ], 500);
        }
    }
}
