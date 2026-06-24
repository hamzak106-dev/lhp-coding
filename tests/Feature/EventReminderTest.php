<?php

use App\Mail\EventReminderMail;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

function attendeeForEventInHours(int $hours): Attendee
{
    $event = Event::factory()->startingInHours($hours)->create(['status' => 'published']);

    return Attendee::factory()->for($event)->create([
        'reminder_3d_sent_at' => null,
        'reminder_24h_sent_at' => null,
    ]);
}

it('queues a 24-hour reminder for events starting within a day', function () {
    $attendee = attendeeForEventInHours(20);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminderMail::class, fn ($m) => $m->window === 'tomorrow' && $m->hasTo($attendee->email));
    expect($attendee->fresh()->reminder_24h_sent_at)->not->toBeNull();
});

it('queues a 3-day reminder for events starting in two-ish days', function () {
    $attendee = attendeeForEventInHours(48);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminderMail::class, fn ($m) => $m->window === 'in 3 days');
    expect($attendee->fresh()->reminder_3d_sent_at)->not->toBeNull();
});

it('does not resend a reminder that was already sent', function () {
    attendeeForEventInHours(20);

    $this->artisan('events:send-reminders');
    $this->artisan('events:send-reminders');

    // Only one email total despite two runs.
    Mail::assertQueued(EventReminderMail::class, 1);
});

it('ignores events outside the reminder windows', function () {
    attendeeForEventInHours(24 * 10); // ten days out — too far

    $this->artisan('events:send-reminders');

    Mail::assertNothingQueued();
});
