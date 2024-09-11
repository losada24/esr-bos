<div>
    <p>Good day,</p>
    <p>This email is a confirmation that your delivery or pickup for materials will be on {{$order->delivery_date}}.</p>
    <p style="font-weight: bold;">Order summary</p>
    <p><span style="font-weight: bold;">Order Number:</span> {{ $order->order_number }}</p>
    <p><span style="font-weight: bold;">Order Name:</span> {{ $order->name }}</p>
    <p><span style="font-weight: bold;">Client Name:</span> {{ $order->client->name }}</p>
    <p>Thank you.</p>
</div>