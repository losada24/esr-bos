<p>Hello {{ $recipientName ?: 'there' }},</p>

<p>
    This order is no longer assigned to you.
</p>

<p>
    <strong>Order:</strong> {{ $order->name ?? ('Order #' . $order->id) }}<br>
    <strong>Client:</strong> {{ optional($order->client)->name ?: 'Not specified' }}<br>
    <strong>Current Owner{{ count($currentOwnerNames) === 1 ? '' : 's' }}:</strong>
    {{ !empty($currentOwnerNames) ? implode(', ', $currentOwnerNames) : 'Not assigned' }}
</p>

<p>Thank you,<br>{{ config('app.name') }}</p>
