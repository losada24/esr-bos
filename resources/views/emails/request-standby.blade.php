<p>Hello,</p>

<p>
    An order has been moved to the <strong>REQUEST STAND BY</strong> status.
</p>

<p>
    <strong>Created By:</strong> {{ $order->user->name ?? 'Unknown User' }}<br>
    <strong>Order Name:</strong> {{ $order->name ?? 'Not specified' }}
</p>

<p>Thank you,<br>{{ config('app.name') }}</p>
