<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controller that displays payment success/failure result pages.
 */
class PaymentResultController extends Controller
{
    /**
     * Display the payment success page.
     *
     * @param Payment $payment The completed payment
     * @param Request $request
     * @return View
     */
    public function success(Payment $payment, Request $request): View
    {
        // Load the donation with campaign relationship
        $payment->load(['donation.campaign', 'donation.user']);

        /** @var view-string $viewName */
        $viewName = 'payment.success';

        $donation = $payment->donation;
        if ($donation === null) {
            abort(404, 'Donation not found for this payment');
        }

        return view($viewName, [
            'payment' => $payment,
            'donation' => $donation,
            'campaign' => $donation->campaign,
            'user' => $donation->user,
        ]);
    }

    /**
     * Display the payment failure page.
     *
     * @param Payment $payment The failed payment
     * @param Request $request
     * @return View
     */
    public function failure(Payment $payment, Request $request): View
    {
        // Load the donation with campaign relationship
        $payment->load(['donation.campaign', 'donation.user']);

        /** @var view-string $viewName */
        $viewName = 'payment.failure';

        $donation = $payment->donation;
        if ($donation === null) {
            abort(404, 'Donation not found for this payment');
        }

        return view($viewName, [
            'payment' => $payment,
            'donation' => $donation,
            'campaign' => $donation->campaign,
            'user' => $donation->user,
        ]);
    }
}
