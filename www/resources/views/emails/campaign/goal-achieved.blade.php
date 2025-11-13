<x-mail::message>
# Congratulations! Campaign Goal Achieved!

Hello {{ $receiver->name }},

Amazing news! Your campaign "{{ $campaign['title'] }}" has reached its funding goal!

**Campaign Summary:**
- **Goal Amount:** {{ $campaign['goal_amount'] ?? '0' }}
- **Current Amount:** {{ $campaign['current_amount'] ?? '0' }}
- **Achievement:** {{ isset($campaign['current_amount']) && isset($campaign['goal_amount']) && $campaign['goal_amount'] > 0 ? round(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 2) : 0 }}%

This is a significant milestone! Thank you for your dedication and to all the generous donors who made this possible.

<x-mail::button :url="config('app.url') . '/campaigns/' . $campaign['id']">
View Campaign
</x-mail::button>

Congratulations on this achievement!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
