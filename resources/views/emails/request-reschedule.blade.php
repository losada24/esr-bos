<p>Hello,</p>

<p>
    An order has been moved to the <strong>REQUEST RE-SCHEDULE</strong> status.
</p>

<p>
    <strong>Order Name:</strong> {{ $order->name ?? 'Not specified' }}<br>
    <strong>Assigned Owner{{ $order->owners->count() === 1 ? '' : 's' }}:</strong>
    {{ $order->owners->pluck('name')->filter()->implode(', ') ?: 'Not assigned' }}
</p>

@if(!empty($note))
    <p><strong>Note:</strong></p>
    <p>{!! nl2br(e($note)) !!}</p>
@endif

<p>Thank you,<br>{{ config('app.name') }}</p>
