<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;

/**
 * Controller for handling payment method related HTTP requests
 *
 * This controller is thin and delegates business logic to the PaymentService
 */
class PaymentMethodController extends Controller
{
    /**
     * Constructor - Inject all dependencies
     *
     * @param PaymentService $paymentService Service for business logic
     */
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Get all enabled payment methods
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $methods = $this->paymentService->getEnabledPaymentMethodsForDisplay();

            return response()->json([
                'data' => $methods,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch payment methods.',
            ], 500);
        }
    }
}
