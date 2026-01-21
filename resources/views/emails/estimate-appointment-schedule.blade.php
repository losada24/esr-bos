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
        $details = 'Order: ' . ($order->order_number ?? $order->name);
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
    An estimate appointment has been scheduled for order
    <strong>{{ $order->order_number ?? $order->name }}</strong>
    @if($appointmentDate)
        on <strong>{{ $appointmentDate }}</strong>
    @endif
    .
</p>

@if($googleCalendarUrl)
    <p><a href="{{ $googleCalendarUrl }}">Add to Google Calendar</a></p>
@endif

<p>The sale form for this appointment is attached for your review.</p>

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
