<x-mail::message>
# Your campaign was rejected

Hello {{ $receiver->name }},

Your campaign was rejected. Please review it and try to submit it again.

**Campaign Details:**
- **Title:** {{ $campaign['title'] }}
@if($campaign['description'])
- **Description:** {{ Str::limit($campaign['description'], 150) }}
@endif
@if($campaign['goal_amount'])
- **Goal Amount:** {{ $campaign['currency']->value ?? '' }} {{ number_format($campaign['goal_amount'], 2) }}
@endif

<x-mail::button :url="route('campaigns.edit', $campaign['id'])">
View Campaign
</x-mail::button>

Please review this campaign and take appropriate action.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
