<x-mail::message>
# Your payment was successful !

Hello {{ $receiver->name }},

Congratulations! Your donation was successful!

**Campaign Details:**
- **Title:** {{ $campaign['title'] }}
- **Goal Amount:** {{ $campaign['currency']->value ?? '' }} {{ number_format($campaign['goal_amount'], 2) }}

<x-mail::button :url="route('campaigns.show', $campaign['id'])">
View Campaign
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
