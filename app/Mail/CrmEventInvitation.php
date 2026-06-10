<?php

namespace App\Mail;

use App\Models\CrmEvent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CrmEventInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CrmEvent $event)
    {
    }

    public function envelope(): Envelope
    {
        $this->event->loadMissing(['host', 'order', 'client']);

        return new Envelope(
            subject: 'Event Invitation: ' . $this->event->title,
        );
    }

    public function content(): Content
    {
        $this->event->loadMissing(['host', 'order', 'client']);

        return new Content(
            view: 'emails.crm-event-invitation',
            with: [
                'event' => $this->event,
                'googleCalendarUrl' => $this->googleCalendarUrl(),
                'startsAt' => $this->startsAt(),
                'endsAt' => $this->endsAt(),
                'logoUrl' => 'cid:reylos-logo',
                'organizerName' => $this->event->host?->name ?? config('app.name'),
                'participantEmails' => $this->participantEmails(),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->icsContent(), 'event-invitation.ics')
                ->withMime('text/calendar'),
        ];
    }

    public function inlineImages(): array
    {
        $logoPath = resource_path('assets/images/logo-reylosglass.png');

        if (!is_file($logoPath)) {
            $logoPath = resource_path('assets/images/logo-reylos.jpg');
        }

        return is_file($logoPath)
            ? [[
                'contentId' => 'reylos-logo',
                'path' => $logoPath,
                'filename' => basename($logoPath),
                'mimeType' => mime_content_type($logoPath) ?: 'image/png',
            ]]
            : [];
    }

    private function startsAt(): ?Carbon
    {
        return $this->event->starts_at ? Carbon::parse($this->event->starts_at, config('app.timezone')) : null;
    }

    private function endsAt(): ?Carbon
    {
        return $this->event->ends_at ? Carbon::parse($this->event->ends_at, config('app.timezone')) : null;
    }

    private function googleCalendarUrl(): ?string
    {
        $start = $this->startsAt();
        $end = $this->endsAt();

        if (!$start || !$end) {
            return null;
        }

        $details = collect([
            $this->event->description,
            $this->event->meeting_link ? 'Meeting link: ' . $this->event->meeting_link : null,
            $this->event->order ? 'Related order: ' . $this->event->order->name : null,
            $this->event->client ? 'Contact: ' . $this->event->client->name : null,
            $this->event->host ? 'Host: ' . $this->event->host->name : null,
        ])->filter()->implode("\n");

        return 'https://www.google.com/calendar/render?action=TEMPLATE'
            . '&text=' . urlencode($this->event->title)
            . '&dates=' . $start->copy()->utc()->format('Ymd\THis\Z') . '/' . $end->copy()->utc()->format('Ymd\THis\Z')
            . '&details=' . urlencode($details)
            . ($this->event->location ? '&location=' . urlencode($this->event->location) : '')
            . '&ctz=' . urlencode((string) config('app.timezone'));
    }

    private function participantEmails(): array
    {
        return collect($this->event->participants ?? [])
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn (string $email) => strtolower(trim($email)))
            ->unique()
            ->values()
            ->all();
    }

    private function icsContent(): string
    {
        $start = $this->startsAt()?->copy()->utc()->format('Ymd\THis\Z');
        $end = $this->endsAt()?->copy()->utc()->format('Ymd\THis\Z');
        $created = now()->utc()->format('Ymd\THis\Z');
        $description = collect([
            $this->event->description,
            $this->event->meeting_link ? 'Meeting link: ' . $this->event->meeting_link : null,
            $this->event->order ? 'Related order: ' . $this->event->order->name : null,
            $this->event->client ? 'Contact: ' . $this->event->client->name : null,
        ])->filter()->implode("\n");

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Reylos BOS//CRM Event//EN',
            'BEGIN:VEVENT',
            'UID:crm-event-' . $this->event->id . '@' . parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:' . $created,
            'DTSTART:' . $start,
            'DTEND:' . $end,
            'SUMMARY:' . $this->icsEscape($this->event->title),
            'DESCRIPTION:' . $this->icsEscape($description),
            'LOCATION:' . $this->icsEscape($this->event->location ?? ''),
        ];

        if ($this->event->meeting_link) {
            $lines[] = 'URL:' . $this->event->meeting_link;
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    private function icsEscape(string $value): string
    {
        return str_replace(["\\", "\n", "\r", ',', ';'], ['\\\\', '\\n', '', '\\,', '\\;'], $value);
    }
}
