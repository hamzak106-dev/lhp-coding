<?php

use App\Mail\AttendeeConfirmationMail;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

it('registers an attendee and queues a confirmation email', function () {
    $event = Event::factory()->create(['status' => 'published']);

    $response = $this->post("/events/{$event->id}/attendees", [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('attendees', [
        'event_id' => $event->id,
        'email' => 'ada@example.com',
        'name' => 'Ada Lovelace',
    ]);

    Mail::assertQueued(AttendeeConfirmationMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));
});

it('is idempotent and does not email twice for the same email', function () {
    $event = Event::factory()->create(['status' => 'published']);

    $this->post("/events/{$event->id}/attendees", ['name' => 'Ada', 'email' => 'ada@example.com']);
    $this->post("/events/{$event->id}/attendees", ['name' => 'Ada Again', 'email' => 'ada@example.com']);

    expect(Attendee::where('event_id', $event->id)->count())->toBe(1);
    Mail::assertQueued(AttendeeConfirmationMail::class, 1);
});

it('validates the registration input', function () {
    $event = Event::factory()->create(['status' => 'published']);

    $this->post("/events/{$event->id}/attendees", ['name' => '', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);

    Mail::assertNothingQueued();
});
