@php
    const DOORS = 1;
    const WINDOWS = 2;
@endphp

<div>
    <p>Good day,</p>
    <p>I hope this email finds you well. I am writing to provide you with the details of a recent order for your records.</p>
    <p style="font-weight: bold;">Order summary</p>
    <p><span style="font-weight: bold;">Client Name:</span> {{ $order->client->name }}</p>
    <p><span style="font-weight: bold;">Order Number:</span> {{ $order->order_number }}</p>
    <p><span style="font-weight: bold;">Order Name:</span> {{ $order->name }}</p>
    <p><span style="font-weight: bold;">Job Address:</span> {{ $order->job_address}}</p>
    <p><span style="font-weight: bold;">Windows:</span> {{ $order->orderProducts->where('type_of_product_id', WINDOWS)->sum('qty')}}</p>
    <p><span style="font-weight: bold;">Door:</span> {{ $order->orderProducts->where('type_of_product_id', DOORS)->sum('qty')}}</p>
    <p><span style="font-weight: bold;">Grand Total:</span> {{ '$' . number_format($order->getGrandTotalPrice(), 2, '.', ',') /*$fmt->formatCurrency($order->getGrandTotalPrice(), 'USD')*/}}</p>
    <p>Thank you.</p>
</div>