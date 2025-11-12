<?php

namespace App\Http\Controllers\Payment;

use App\Enums\Payment\FailureReasonEnum;
use App\Http\Controllers\Controller;
use App\Models\Payment\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class FakePaymentController extends Controller
{
    /**
     * Display the fake payment service page.
     */
    public function show(Payment $payment): View
    {
        // Authorize access using the policy
        Gate::authorize('access', $payment);

        // Get all failure reasons for the dropdown
        $failureReasons = collect(FailureReasonEnum::cases())->map(function (FailureReasonEnum $reason) {
            return [
                'value' => $reason->value,
                'label' => $reason->label(),
            ];
        })->toArray();

        /** @var view-string $viewName */
        $viewName = 'payment.fake';

        return view($viewName, [
            'payment' => $payment,
            'failureReasons' => $failureReasons,
        ]);
    }
}
