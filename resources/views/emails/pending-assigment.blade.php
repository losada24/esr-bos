

<p>Hello,</p>

<p>
    You have a new order pending assignment:
    <strong>{{ $order->order_number ?? $order->name }}</strong>.
</p>

<p>
    <strong>Client:</strong> {{ $order->client->name ?? 'Not specified' }}<br>
    <strong>VIP Client:</strong> {{ ($order->client->vip_clients ?? false) ? 'Yes' : 'No' }}<br>
    @if(($order->client->vip_notes ?? '') !== '')
         <strong>VIP Notes:</strong> {{ $order->client->vip_notes }}<br>
    @endif
    <strong>Order Name:</strong> {{ $order->name ?? 'Not specified' }}<br>
    <strong>Order Number:</strong> {{ $order->order_number ?? 'Not assigned' }}
</p>

<p>
    <strong>Order Type:</strong> {{ $order->order_type ?? 'Not specified' }}
    @if($order->is_supply)
        (SUPPLY)
    @endif
    <br>
    <strong>Supply Only:</strong> {{ $order->is_supply ? 'Yes' : 'No' }}
</p>

<p>Thank you,<br>{{ config('app.name') }}</p>
