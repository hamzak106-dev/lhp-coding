<?php

namespace App\Console\Commands;

use App\Mail\EventReminderMail;
use App\Models\Attendee;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Queue reminder emails for attendees of upcoming events (3 days and 24 hours out).';

    public function handle(): int
    {
        $now = now()->getTimestamp();

        // 24-hour reminder: events starting within the next day.
        $sent24h = $this->dispatchWindow(
            from: $now,
            to: $now + 86_400,
            sentColumn: 'reminder_24h_sent_at',
            window: 'tomorrow',
        );

        // 3-day reminder: events starting between 24 and 72 hours out.
        $sent3d = $this->dispatchWindow(
            from: $now + 86_400,
            to: $now + 259_200,
            sentColumn: 'reminder_3d_sent_at',
            window: 'in 3 days',
        );

        $this->info("Queued {$sent24h} 24-hour and {$sent3d} 3-day reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Queue a reminder for every attendee of an event starting in the given
     * window that hasn't already received this window's email, then stamp it.
     */
    private function dispatchWindow(int $from, int $to, string $sentColumn, string $window): int
    {
        $count = 0;

        Attendee::query()
            ->whereNull($sentColumn)
            ->whereHas('event', fn (Builder $q) => $q->whereBetween('created_time', [$from, $to]))
            ->with('event')
            ->chunkById(500, function ($attendees) use (&$count, $sentColumn, $window) {
                foreach ($attendees as $attendee) {
                    Mail::to($attendee->email)->queue(new EventReminderMail($attendee, $window));
                    $attendee->forceFill([$sentColumn => now()])->save();
                    $count++;
                }
            });

        return $count;
    }
}
