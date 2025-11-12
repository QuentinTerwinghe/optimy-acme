<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller that displays payment result pages based on payment status.
 * Uses a single endpoint that determines the view based on the payment status.
 */
class PaymentResultController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the payment result page (success or failure) based on payment status.
     *
     * @param Payment $payment The payment to display
     * @return View
     */
    public function show(Payment $payment): View
    {
        // Authorize the user can view this payment result
        $this->authorize('view', $payment);

        // Load additional relationships
        $payment->load(['donation.campaign', 'donation.user']);

        $donation = $payment->donation;
        if ($donation === null) {
            abort(404, 'Donation not found for this payment');
        }

        // Determine which view to show based on payment status
        /** @var view-string $viewName */
        $viewName = $payment->isCompleted() ? 'payment.success' : 'payment.failure';

        return view($viewName, [
            'payment' => $payment,
            'donation' => $donation,
            'campaign' => $donation->campaign,
            'user' => $donation->user,
        ]);
    }
}
