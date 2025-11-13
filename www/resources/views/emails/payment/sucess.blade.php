<x-mail::message>
# Your payment was successful !

Hello {{ $receiver->name }},

Congratulations! Your payment was successful!

**Campaign Details:**
- **Title:** {{ $campaign['title'] }}
- **Amount:** {{ $campaign['currency']->value ?? '' }} {{ number_format($campaign['goal_amount'], 2) }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
