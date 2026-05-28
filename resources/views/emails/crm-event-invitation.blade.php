@php
    $timezone = config('app.timezone');
    $dateLabel = $startsAt ? $startsAt->format('l, F j, Y') : 'Date pending';
    $timeLabel = $startsAt && $endsAt
        ? $startsAt->format('h:i A') . ' - ' . $endsAt->format('h:i A') . ' (' . $timezone . ')'
        : 'Time pending';
    $whenLabel = trim($dateLabel . ', ' . $timeLabel);
    $whereLabel = $event->location ?: ($event->online_meeting ? 'Online meeting' : 'REYLOS GLASS');
    $relatedLabel = $event->order?->name ?? $event->client?->name;
    $organizerEmail = $event->host?->email;
    $rsvpSubject = rawurlencode('RSVP: ' . $event->title);
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event->title }}</title>
</head>
<body style="margin:0;background:#f5f7fa;font-family:Arial,Helvetica,sans-serif;color:#2f3a45;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#f5f7fa;">
        <tr>
            <td style="padding:0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#ffffff;border-bottom:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:20px 32px;">
                            <img src="{{ $logoUrl }}" alt="Reylos Glass" width="150" style="display:block;border:0;max-width:150px;height:auto;">
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="padding:44px 32px 36px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;max-width:780px;">
                                <tr>
                                    <td width="42" valign="top" style="padding-top:2px;">
                                        <div style="width:34px;height:34px;border:3px solid #2563eb;border-radius:3px;color:#2563eb;font-size:24px;line-height:28px;text-align:center;font-weight:700;">&#10003;</div>
                                    </td>
                                    <td valign="top" style="padding-left:8px;">
                                        <div style="font-size:18px;line-height:1.45;color:#4b5563;">
                                            <strong style="color:#374151;">{{ $organizerName }}</strong>
                                            invited you to
                                            <strong style="color:#374151;text-transform:uppercase;">{{ $event->title }}</strong>.
                                        </div>

                                        <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:26px;">
                                            <tr>
                                                <td valign="top" style="width:58px;padding:0 0 14px;color:#8b9096;font-size:14px;font-weight:700;">When</td>
                                                <td valign="top" style="padding:0 0 14px 0;color:#4b5563;font-size:14px;line-height:1.45;">{{ $whenLabel }}</td>
                                            </tr>
                                            <tr>
                                                <td valign="top" style="width:58px;padding:0 0 14px;color:#8b9096;font-size:14px;font-weight:700;">Where</td>
                                                <td valign="top" style="padding:0 0 14px 0;color:#4b5563;font-size:14px;line-height:1.45;">
                                                    @if($event->meeting_link)
                                                        <a href="{{ $event->meeting_link }}" style="color:#2563eb;text-decoration:none;font-weight:700;">{{ $whereLabel }}</a>
                                                    @else
                                                        {{ $whereLabel }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td valign="top" style="width:58px;padding:0 0 14px;color:#8b9096;font-size:14px;font-weight:700;">Who</td>
                                                <td valign="top" style="padding:0 0 14px 0;color:#4b5563;font-size:14px;line-height:1.45;">{{ $organizerName }}</td>
                                            </tr>
                                            @if(count($participantEmails) > 0)
                                                <tr>
                                                    <td valign="top" style="width:58px;padding:0 0 14px;color:#8b9096;font-size:14px;font-weight:700;">Guests</td>
                                                    <td valign="top" style="padding:0 0 14px 0;color:#4b5563;font-size:14px;line-height:1.55;">
                                                        {{ implode(', ', $participantEmails) }}
                                                    </td>
                                                </tr>
                                            @endif
                                            @if($relatedLabel)
                                                <tr>
                                                    <td valign="top" style="width:58px;padding:0 0 14px;color:#8b9096;font-size:14px;font-weight:700;">Related</td>
                                                    <td valign="top" style="padding:0 0 14px 0;color:#4b5563;font-size:14px;line-height:1.45;">{{ $relatedLabel }}</td>
                                                </tr>
                                            @endif
                                        </table>

                                        @if($event->description)
                                            <div style="margin-top:8px;max-width:680px;border-left:3px solid #d1d5db;padding:10px 0 10px 14px;color:#4b5563;font-size:14px;line-height:1.6;">
                                                {!! nl2br(e($event->description)) !!}
                                            </div>
                                        @endif

                                        <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:48px;">
                                            <tr>
                                                @if($organizerEmail)
                                                    <td style="padding-right:20px;">
                                                        <a href="mailto:{{ $organizerEmail }}?subject={{ $rsvpSubject }}&body=Yes" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:4px;padding:11px 22px;font-size:14px;font-weight:700;">&#10003; Yes</a>
                                                    </td>
                                                    <td style="padding-right:20px;">
                                                        <a href="mailto:{{ $organizerEmail }}?subject={{ $rsvpSubject }}&body=Maybe" style="display:inline-block;background:#ffffff;color:#6b7280;text-decoration:none;border:1px solid #d1d5db;border-radius:4px;padding:10px 22px;font-size:14px;font-weight:700;">? Maybe</a>
                                                    </td>
                                                    <td style="padding-right:20px;">
                                                        <a href="mailto:{{ $organizerEmail }}?subject={{ $rsvpSubject }}&body=No" style="display:inline-block;background:#ffffff;color:#6b7280;text-decoration:none;border:1px solid #d1d5db;border-radius:4px;padding:10px 22px;font-size:14px;font-weight:700;">x No</a>
                                                    </td>
                                                @endif
                                                @if($googleCalendarUrl)
                                                    <td>
                                                        <a href="{{ $googleCalendarUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:4px;padding:11px 18px;font-size:14px;font-weight:700;">Add to Google Calendar</a>
                                                    </td>
                                                @endif
                                            </tr>
                                        </table>

                                        @if($event->meeting_link)
                                            <div style="margin-top:20px;font-size:13px;color:#6b7280;">
                                                Meeting link:
                                                <a href="{{ $event->meeting_link }}" style="color:#2563eb;text-decoration:none;font-weight:700;">{{ $event->meeting_link }}</a>
                                            </div>
                                        @endif

                                        <p style="margin:34px 0 0;color:#8b9096;font-size:12px;line-height:1.6;">
                                            A calendar file is attached for Outlook, Apple Calendar, and other calendar apps.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#f0f2f5;">
                    <tr>
                        <td style="padding:18px 32px;color:#8b9096;font-size:12px;">
                            Regards,<br>
                            Reylos Glass
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
