<x-mail::message>
# New Donation Received!

Hello {{ $receiver->name }},

Great news! Your campaign "{{ $campaign['title'] }}" has received a new donation!

**Donation Details:**
- **Amount:** {{ $donation['amount'] ?? '' }}
- **Date:** {{ isset($donation['created_at']) ? \Carbon\Carbon::parse($donation['created_at'])->format('F j, Y g:i A') : 'N/A' }}

**Campaign Progress:**
- **Current Amount:** {{ $campaign['current_amount'] ?? '0' }}
- **Goal Amount:** {{ $campaign['goal_amount'] ?? '0' }}
- **Progress:** {{ isset($campaign['current_amount']) && isset($campaign['goal_amount']) && $campaign['goal_amount'] > 0 ? round(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 2) : 0 }}%

<x-mail::button :url="config('app.url') . '/campaigns/' . $campaign['id']">
View Campaign
</x-mail::button>

Thank you for making a difference!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
