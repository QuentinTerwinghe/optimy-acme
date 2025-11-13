<x-mail::message>
# Your payment was successful!

Hello {{ $receiver->name }},

Congratulations! Your payment was successful!

**Payment Details:**
- **Amount:** {{ $payment['currency'] ?? '' }} {{ number_format($payment['amount'], 2) }}
- **Transaction ID:** {{ $payment['transaction_id'] ?? 'N/A' }}
- **Status:** Completed

Thank you for your generous contribution!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
