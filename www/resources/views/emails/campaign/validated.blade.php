<x-mail::message>
# Your campaign was validated !

Hello {{ $receiver->name }},

Congratulations! Your campaign was validated!

**Campaign Details:**
- **Title:** {{ $campaign['title'] }}
@if($campaign['description'])
- **Description:** {{ Str::limit($campaign['description'], 150) }}
@endif
@if($campaign['goal_amount'])
- **Goal Amount:** {{ $campaign['currency']->value ?? '' }} {{ number_format($campaign['goal_amount'], 2) }}
@endif

<x-mail::button :url="route('campaigns.show', $campaign['id'])">
View Campaign
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
