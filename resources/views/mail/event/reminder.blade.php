<x-mail::message>
# {{ $event->title() }} is {{ $window }} ⏰

Hi {{ $attendee->name }},

Just a reminder that **{{ $event->title() }}** is happening **{{ $window }}**.

<x-mail::panel>
**When:** {{ $event->formattedStart() }}
**Where:** {{ $event->venueName() ?? 'Venue TBA' }} — {{ $event->locationLabel() }}
</x-mail::panel>

<x-mail::button :url="config('app.url') . '/events/' . $event->id">
View event details
</x-mail::button>

Looking forward to seeing you,
{{ config('app.name') }}
</x-mail::message>
