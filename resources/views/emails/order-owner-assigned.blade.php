@php
    $appointmentStart = $order->schedule_appointment
        ? \Carbon\Carbon::parse($order->schedule_appointment, config('app.timezone'))
        : null;
    $showAppointment = $appointmentStart
        ? $appointmentStart->copy()->startOfDay()->greaterThanOrEqualTo(now(config('app.timezone'))->startOfDay())
        : false;
@endphp

<p>Hello {{ $recipientName ?: 'there' }},</p>

<p>
    This order has been assigned to you.
</p>

<p>
    <strong>Order:</strong> {{ $order->name ?? ('Order #' . $order->id) }}<br>
    <strong>Client:</strong> {{ optional($order->client)->name ?: 'Not specified' }}<br>
    <strong>Current Owner{{ count($currentOwnerNames) === 1 ? '' : 's' }}:</strong>
    {{ !empty($currentOwnerNames) ? implode(', ', $currentOwnerNames) : 'Not assigned' }}<br>
    @if($showAppointment)
        <strong>Appointment:</strong> {{ $appointmentStart->format('M d, Y h:i A') }}<br>
    @endif
</p>

<p>Thank you,<br>{{ config('app.name') }}</p>
