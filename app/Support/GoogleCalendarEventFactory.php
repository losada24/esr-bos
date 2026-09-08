<?php

namespace App\Support;

use App\Models\CrmEvent;
use Carbon\Carbon;

class GoogleCalendarEventFactory
{
    public function __construct(
        private readonly ?string $timezone = null,
        private readonly ?string $source = null,
    ) {}

    public function deterministicId(int $crmEventId): string
    {
        // Lower-case hexadecimal is valid base32hex syntax accepted by Google Calendar.
        return hash('sha256', $this->eventSource().':crm-event:'.$crmEventId);
    }

    public function googleTitle(string $title): string
    {
        $source = $this->eventSource();

        if (preg_match('/\s*—\s*'.preg_quote($source, '/').'\s*$/iu', $title) === 1) {
            return $title;
        }

        return rtrim($title).' — '.$source;
    }

    public function payload(CrmEvent $event): array
    {
        $timezone = $this->calendarTimezone();
        $description = collect([
            $event->description,
            $event->host?->email
                ? 'Host: '.($event->host?->name ? $event->host->name.' <'.$event->host->email.'>' : $event->host->email)
                : null,
            $event->online_meeting && $event->meeting_link
                ? 'Online meeting: '.$event->meeting_link
                : null,
        ])->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->implode("\n\n");

        $payload = [
            'id' => $this->deterministicId((int) $event->id),
            'summary' => $this->googleTitle((string) $event->title),
            'description' => $description,
            'location' => $event->location,
            'start' => [
                'dateTime' => Carbon::parse($event->starts_at)->timezone($timezone)->toRfc3339String(),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => Carbon::parse($event->ends_at)->timezone($timezone)->toRfc3339String(),
                'timeZone' => $timezone,
            ],
            'reminders' => $this->reminders($event),
            'extendedProperties' => [
                'private' => [
                    'crm_event_id' => (string) $event->id,
                    'event_source' => $this->eventSource(),
                ],
            ],
        ];

        $recurrence = $this->recurrence($event);
        if ($recurrence !== null) {
            $payload['recurrence'] = [$recurrence];
        }

        return array_filter($payload, static fn ($value) => $value !== null);
    }

    private function reminders(CrmEvent $event): array
    {
        if (! $event->reminder_enabled || $event->reminder_minutes_before === null) {
            return ['useDefault' => false, 'overrides' => []];
        }

        return [
            'useDefault' => false,
            'overrides' => [[
                'method' => 'popup',
                'minutes' => (int) $event->reminder_minutes_before,
            ]],
        ];
    }

    private function recurrence(CrmEvent $event): ?string
    {
        if (! $event->is_repeating) {
            return null;
        }

        $parts = match ($event->recurrence_frequency) {
            'weekly' => ['FREQ=WEEKLY', 'INTERVAL='.max(1, (int) ($event->recurrence_interval ?? 1)), 'BYDAY='.$this->weekday($event)],
            'biweekly' => ['FREQ=WEEKLY', 'INTERVAL=2', 'BYDAY='.$this->weekday($event)],
            'monthly' => ['FREQ=MONTHLY', 'INTERVAL='.max(1, (int) ($event->recurrence_interval ?? 1)), 'BYMONTHDAY='.max(1, min(31, (int) ($event->recurrence_month_day ?? 1)))],
            default => null,
        };

        if ($parts === null) {
            return null;
        }

        if ($event->recurrence_ends_at) {
            $parts[] = 'UNTIL='.Carbon::parse($event->recurrence_ends_at, $this->calendarTimezone())
                ->endOfDay()
                ->utc()
                ->format('Ymd\THis\Z');
        }

        return 'RRULE:'.implode(';', $parts);
    }

    private function weekday(CrmEvent $event): string
    {
        return ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'][(int) ($event->recurrence_weekday ?? 0)] ?? 'SU';
    }

    private function calendarTimezone(): string
    {
        return $this->timezone ?? (string) config('google_calendar.timezone', 'America/New_York');
    }

    private function eventSource(): string
    {
        return trim($this->source ?? (string) config('google_calendar.event_source', 'ESR BOS')) ?: 'ESR BOS';
    }
}
