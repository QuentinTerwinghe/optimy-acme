<x-mail::message>
# Your payment failed

Hello {{ $receiver->name }},

Unfortunately, your payment could not be processed.

**Payment Details:**
- **Amount:** {{ $payment['currency'] ?? '' }} {{ number_format($payment['amount'], 2) }}
- **Status:** Failed
@if(isset($payment['error_message']) && $payment['error_message'])
- **Error:** {{ $payment['error_message'] }}
@endif

Please try again or contact support if the problem persists.

<x-mail::button :url="config('app.url')">
Try Again
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
