@php
    $appointmentDate = $order->schedule_appointment
        ? \Carbon\Carbon::parse($order->schedule_appointment)->format('m/d/Y h:i A')
        : null;

    $appointmentStart = $order->schedule_appointment
        ? \Carbon\Carbon::parse($order->schedule_appointment, config('app.timezone'))
        : null;
    $appointmentEnd = $appointmentStart?->copy()->addHour();
    $googleCalendarUrl = null;

    if ($appointmentStart && $appointmentEnd) {
        $eventTitle = 'Estimate appointment - ' . ($order->order_number ?? $order->name);
        $clientPhone = $order->client?->phone;
        $clientPhoneLabel = $clientPhone ?: 'No phone';
        $details = 'Order: ' . ($order->order_number ?? $order->name)
            . "\n" . 'Client phone: ' . $clientPhoneLabel;
        $location = trim(implode(', ', array_filter([
            $order->job_address,
            $order->job_city,
            $order->job_state,
            $order->job_zip,
        ])));
        $dates = $appointmentStart->format('Ymd\THis') . '/' . $appointmentEnd->format('Ymd\THis');

        $googleCalendarUrl = 'https://www.google.com/calendar/render?action=TEMPLATE'
            . '&text=' . urlencode($eventTitle)
            . '&dates=' . $dates
            . '&details=' . urlencode($details)
            . ($location ? '&location=' . urlencode($location) : '')
            . '&ctz=' . urlencode(config('app.timezone'));
    }

    $orderNotes = $orderNotes ?? $order->notes()
        ->with('user')
        ->orderBy('created_at')
        ->get();
@endphp

<p>Hello,</p>

<p>
    Order <strong>{{ $order->order_number ?? $order->name }}</strong> has been moved to
    <strong>ESTIMATE &amp; APPT SCHEDULE</strong>.
</p>

@if($appointmentDate)
    <p>The current appointment is set for <strong>{{ $appointmentDate }}</strong>.</p>
@else
    <p>An appointment date has not been added yet. Please review the attached sale form and the associated notes below.</p>
@endif

@if($googleCalendarUrl)
    <p><a href="{{ $googleCalendarUrl }}">Add to Google Calendar</a></p>
@endif

<p>The sale form is attached for your review.</p>

@if($orderNotes->isNotEmpty())
    
    <p><strong>Associated Notes:</strong></p>
    <ul>
        @foreach($orderNotes as $note)
            <li>
                @php
                    
                    $author = $note->user?->name;
                    $timestamp = $note->created_at
                        ? \Carbon\Carbon::parse($note->created_at)->format('m/d/Y h:i A')
                        : null;
                @endphp
                <span>{!! nl2br(e($note->content)) !!}</span>
                @if($author || $timestamp)
                    <br>
                    <small>
                        @if($author)
                            By {{ $author }}
                        @endif
                        @if($author && $timestamp)
                            ·
                        @endif
                        @if($timestamp)
                            {{ $timestamp }}
                        @endif
                    </small>
                @endif
            </li>
        @endforeach
    </ul>
@endif

<p>Thank you,<br>{{ config('app.name') }}</p>
