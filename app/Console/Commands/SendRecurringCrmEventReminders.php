<?php

namespace App\Console\Commands;

use App\Jobs\SendGmailEmail;
use App\Mail\CrmEventInvitation;
use App\Models\CrmEvent;
use App\Models\CrmEventOccurrenceEmail;
use App\Support\CrmEventRecurrence;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

class SendRecurringCrmEventReminders extends Command
{
    protected $signature = 'crm-events:send-recurring-reminders';

    protected $description = 'Send reminder emails for due recurring CRM event occurrences.';

    public function handle(CrmEventRecurrence $recurrence): int
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $now = Carbon::now($timezone);
        $sent = 0;

        CrmEvent::query()
            ->with(['host', 'order', 'client'])
            ->where('is_repeating', true)
            ->where('status', '!=', 'Cancelled')
            ->where('reminder_enabled', true)
            ->whereNotNull('reminder_minutes_before')
            ->chunkById(100, function ($events) use ($recurrence, $now, &$sent) {
                foreach ($events as $event) {
                    $occurrence = $recurrence->nextOccurrenceDueForReminder($event, $now);

                    if (!$occurrence) {
                        continue;
                    }

                    $emails = $this->participantEmails($event);
                    if ($emails === []) {
                        continue;
                    }

                    if (!$this->reserveOccurrence($event->id, $occurrence['starts_at'], $occurrence['ends_at'], $now)) {
                        continue;
                    }

                    foreach ($emails as $email) {
                        SendGmailEmail::dispatch(
                            $email,
                            new CrmEventInvitation($event, $occurrence['starts_at'], $occurrence['ends_at']),
                            allowInactiveUserRecipient: true
                        )->onQueue('emails');
                    }

                    $sent++;
                }
            });

        $this->info("Recurring CRM event reminders processed: {$sent}");

        return self::SUCCESS;
    }

    private function reserveOccurrence(int $eventId, Carbon $startsAt, Carbon $endsAt, Carbon $sentAt): bool
    {
        try {
            CrmEventOccurrenceEmail::create([
                'crm_event_id' => $eventId,
                'occurrence_starts_at' => $startsAt,
                'occurrence_ends_at' => $endsAt,
                'sent_at' => $sentAt,
            ]);

            return true;
        } catch (QueryException) {
            return false;
        }
    }

    private function participantEmails(CrmEvent $event): array
    {
        return collect($event->participants ?? [])
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn (string $email) => strtolower(trim($email)))
            ->unique()
            ->values()
            ->all();
    }
}
