<?php

namespace App\Http\Controllers;

use App\Mail\AttendeeConfirmationMail;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AttendeeController extends Controller
{
    /**
     * Register interest/attendance for an event and email a confirmation.
     * Idempotent per (event, email) thanks to the unique constraint.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'status' => ['nullable', 'in:interested,attending'],
        ]);

        $attendee = Attendee::firstOrCreate(
            ['event_id' => $event->id, 'email' => $validated['email']],
            ['name' => $validated['name'], 'status' => $validated['status'] ?? 'attending'],
        );

        if (! $attendee->wasRecentlyCreated) {
            return back()->with('toast', [
                'type' => 'info',
                'message' => "You're already on the list for this event.",
            ]);
        }

        Mail::to($attendee->email)->queue(new AttendeeConfirmationMail($attendee));

        return back()->with('toast', [
            'type' => 'success',
            'message' => "You're on the list — check your email for confirmation.",
        ]);
    }
}
