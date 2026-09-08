<?php

namespace App\Jobs;

use App\Contracts\GoogleCalendarGateway;
use App\Models\CrmEvent;
use App\Models\CrmEventGoogleCalendarSync;
use App\Support\GoogleCalendarEventFactory;
use App\Support\GoogleCalendarInternalDomainMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SyncCrmEventToGoogleCalendars implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300, 600];

    public function __construct(public readonly int $crmEventId) {}

    public function handle(
        GoogleCalendarGateway $calendar,
        GoogleCalendarInternalDomainMatcher $domainMatcher,
        GoogleCalendarEventFactory $eventFactory,
    ): void {
        if (! config('google_calendar.enabled')) {
            return;
        }

        $event = CrmEvent::withTrashed()->with('host')->find($this->crmEventId);
        if (! $event) {
            return;
        }

        $desiredEmails = $this->desiredEmails($event, $domainMatcher);
        $trackingRows = CrmEventGoogleCalendarSync::query()
            ->where('crm_event_id', $event->id)
            ->get()
            ->keyBy('user_email');

        foreach ($desiredEmails as $email) {
            if (! $trackingRows->has($email)) {
                try {
                    $tracking = CrmEventGoogleCalendarSync::firstOrCreate(
                        ['crm_event_id' => $event->id, 'user_email' => $email],
                        [
                            'google_event_id' => $eventFactory->deterministicId((int) $event->id),
                            'status' => CrmEventGoogleCalendarSync::STATUS_PENDING,
                        ]
                    );
                } catch (QueryException) {
                    // A parallel retry may have created the unique event/email row first.
                    $tracking = CrmEventGoogleCalendarSync::query()
                        ->where('crm_event_id', $event->id)
                        ->where('user_email', $email)
                        ->firstOrFail();
                }

                $trackingRows->put($email, $tracking);
            }
        }

        $errors = [];
        foreach ($trackingRows as $email => $tracking) {
            $shouldExist = $desiredEmails->contains($email);

            if (! $shouldExist && $tracking->status === CrmEventGoogleCalendarSync::STATUS_DELETED) {
                continue;
            }

            $tracking->update([
                'status' => CrmEventGoogleCalendarSync::STATUS_PENDING,
                'last_error' => null,
            ]);

            try {
                if ($shouldExist) {
                    $calendar->upsert($event, $email, $tracking->google_event_id);
                    $status = CrmEventGoogleCalendarSync::STATUS_SYNCED;
                } else {
                    $calendar->delete($email, $tracking->google_event_id);
                    $status = CrmEventGoogleCalendarSync::STATUS_DELETED;
                }

                $tracking->update([
                    'status' => $status,
                    'last_error' => null,
                    'synced_at' => now(),
                ]);
            } catch (Throwable $exception) {
                $message = Str::limit($exception->getMessage(), 2000, '');
                $tracking->update([
                    'status' => CrmEventGoogleCalendarSync::STATUS_FAILED,
                    'last_error' => $message,
                ]);
                $errors[] = $email.': '.$message;
            }
        }

        if ($errors !== []) {
            throw new RuntimeException('Google Calendar synchronization failed for '.implode('; ', $errors));
        }
    }

    public function failed(Throwable $exception): void
    {
        CrmEventGoogleCalendarSync::query()
            ->where('crm_event_id', $this->crmEventId)
            ->where('status', CrmEventGoogleCalendarSync::STATUS_PENDING)
            ->update([
                'status' => CrmEventGoogleCalendarSync::STATUS_FAILED,
                'last_error' => Str::limit($exception->getMessage(), 2000, ''),
            ]);
    }

    private function desiredEmails(CrmEvent $event, GoogleCalendarInternalDomainMatcher $domainMatcher)
    {
        if ($event->trashed() || $event->status === 'Cancelled') {
            return collect();
        }

        return collect([$event->host?->email])
            ->merge($event->participants ?? [])
            ->filter(fn ($email) => is_string($email) && $domainMatcher->isInternal($email))
            ->map(fn (string $email) => strtolower(trim($email)))
            ->unique()
            ->values();
    }
}
