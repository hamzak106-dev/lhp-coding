<x-mail::message>
# You're on the list! 🎟️

Hi {{ $attendee->name }},

Thanks for registering for **{{ $event->title() }}**. We've saved your spot.

<x-mail::panel>
**When:** {{ $event->formattedStart() }}
**Where:** {{ $event->venueName() ?? 'Venue TBA' }} — {{ $event->locationLabel() }}
</x-mail::panel>

@if ($event->description())
{{ $event->description() }}
@endif

We'll send you a reminder as the event approaches.

<x-mail::button :url="config('app.url') . '/events/' . $event->id">
View event
</x-mail::button>

See you there,
{{ config('app.name') }}
</x-mail::message>
