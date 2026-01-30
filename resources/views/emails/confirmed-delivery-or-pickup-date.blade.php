<div>
    <p>Good day,</p>
    <p>This email is a confirmation that your delivery or pickup for materials will be on {{ \Carbon\Carbon::parse($order->delivery_date)->format('m-d-Y')}}. </p>
    <p style="font-weight: bold;">Order summary</p>
    <p><span style="font-weight: bold;">Order Number:</span> {{ $order->order_number }}</p>
    <p><span style="font-weight: bold;">Order Name:</span> {{ $order->name }}</p>
    <p><span style="font-weight: bold;">Client Name:</span> {{ optional($order->client)->name ?? 'Not specified' }}</p>
    <p><span style="font-weight: bold;">Your corresponding payment is:</span> {{'$' . number_format($order->cost_delivery  , 2, '.', ',') }}</p>
    <p>Thank you.</p>
</div>
