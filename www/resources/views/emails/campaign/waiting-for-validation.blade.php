<x-mail::message>
# New Campaign Awaiting Validation

Hello {{ $receiver->name }},

A new campaign has been submitted and is waiting for validation.

**Campaign Details:**
- **Title:** {{ $campaign->title }}
- **Created by:** {{ $creator->name }} ({{ $creator->email }})
@if($campaign->description)
- **Description:** {{ Str::limit($campaign->description, 150) }}
@endif
@if($campaign->goal_amount)
- **Goal Amount:** {{ $campaign->currency?->value ?? '' }} {{ number_format($campaign->goal_amount, 2) }}
@endif

<x-mail::button :url="route('campaigns.show', $campaign->id)">
View Campaign
</x-mail::button>

Please review this campaign and take appropriate action.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
