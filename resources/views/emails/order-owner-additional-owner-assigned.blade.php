<p>Hello {{ $recipientName ?: 'there' }},</p>

<p>
    Another seller has been added to this order while you remain assigned to it.
</p>

<p>
    <strong>Order:</strong> {{ $order->name ?? ('Order #' . $order->id) }}<br>
    <strong>Client:</strong> {{ optional($order->client)->name ?: 'Not specified' }}<br>
    <strong>New Seller{{ count($addedOwnerNames) === 1 ? '' : 's' }}:</strong>
    {{ !empty($addedOwnerNames) ? implode(', ', $addedOwnerNames) : 'Not specified' }}<br>
    <strong>Current Owner{{ count($currentOwnerNames) === 1 ? '' : 's' }}:</strong>
    {{ !empty($currentOwnerNames) ? implode(', ', $currentOwnerNames) : 'Not assigned' }}
</p>

<p>Thank you,<br>{{ config('app.name') }}</p>
