<div>
<p>Good day,</p>
<p>I hope this email finds you well. I am writing to provide you with the details of a recent order for your records.</p>
    
    @if ($displaySummary)
    <p style="font-weight: bold;">Order summary</p>
      <p><span style="font-weight: bold;">Delivery Date:</span> {{$order->delivery_date}}</p>
      <p><span style="font-weight: bold;">Installation Date:</span> {{$order->installation_date}}</p>
      <p><span style="font-weight: bold;">Delivery Cost:</span> {{'$' . number_format($order->cost_delivery  , 2, '.', ',') }}</p>
      <p><span style="font-weight: bold;">City Fee Cost:</span> {{'$' . number_format($order->cost_city_fee , 2, '.', ',')}}</p>
      <p><span style="font-weight: bold;">Order Number:</span> {{ $order->order_number }}</p>
      <p><span style="font-weight: bold;">Order Name:</span> {{ $order->name }}</p>
      <p><span style="font-weight: bold;">Client Name:</span> {{ $order->client->name }}</p>
      <p><span style="font-weight: bold;">Client Phone:</span> {{ $order->client->phone }}</p>
      <p><span style="font-weight: bold;">Job Address:</span> {{ $order->job_address}}</p>
      <p><span style="font-weight: bold;">Supervisor:</span> {{ $order->supervisor->name }}</p>
      <p><span style="font-weight: bold;">Installer:</span></p>
      <ul>
        @foreach ($order->installationTeams as $installationTeam)
          <li>{{ $installationTeam->user->name }}</li>
        @endforeach
      </ul>
      <p><span style="font-weight: bold;">City Permits:</span> {{ $order->city_permits ? 'Yes' : 'No' }}</p>
    @endif
    <p>Thank you.</p>
</div>