

<div>
    <p>Good day,</p>
    <p>I hope this email finds you well. I am writing to provide you with the details of a recent order for your records.</p>
    <p style="font-weight: bold;">Order summary</p>
    <p><span style="font-weight: bold;">Client Name:</span> {{ optional($order->client)->name ?? 'Not specified' }}</p>
    <p><span style="font-weight: bold;">Order Number:</span> {{ $order->order_number }}</p>
    <p><span style="font-weight: bold;">Order Name:</span> {{ $order->name }}</p>
    <p><span style="font-weight: bold;">Job Address:</span> {{ 
                        ($order->job_address ?? '') .
                        (!empty($order->city) ? ', ' . $order->city : '') .
                        (!empty($order->job_state) ? ', ' . $order->job_state : '') .
                        (!empty($order->job_zip) ? ', ' . $order->job_zip : '') 
                    }}</p>
    <p><span style="font-weight: bold;">Windows:</span> {{ $order->orderProducts->where('type_of_product_id', 2)->sum('qty')}}</p>
    <p><span style="font-weight: bold;">Door:</span> {{ $order->orderProducts->where('type_of_product_id', 1)->sum('qty')}}</p>
    <p><span style="font-weight: bold;">Sliding Door:</span> {{ $order->orderProducts->where('product_category_id', 2)->sum('qty')}}</p>
    <p><span style="font-weight: bold;">French Door:</span> {{ $order->orderProducts->where('product_category_id', 1)->sum('qty')}}</p>
    <p><span style="font-weight: bold;">Grand Total:</span> {{ '$' . number_format($order->getGrandTotalPrice(), 2, '.', ',') /*$fmt->formatCurrency($order->getGrandTotalPrice(), 'USD')*/}}</p>
    <p>Thank you.</p>
</div>
