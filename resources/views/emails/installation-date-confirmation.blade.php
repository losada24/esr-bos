<div>
    <p>Good day,</p>
    <p>This email is a confirmation that your installation will be on {{$order->installation_date}}.</p>
    @if ($displaySummary)
      <p style="font-weight: bold;">Order summary</p>
      <p><span style="font-weight: bold;">Order Number:</span> {{ $order->order_number }}</p>
      <p><span style="font-weight: bold;">Order Name:</span> {{ $order->name }}</p>
      <p><span style="font-weight: bold;">Client Name:</span> {{ $order->client->name }}</p>
      <p><span style="font-weight: bold;">Supervisor:</span> {{ $order->supervisor->name }}</p>
      <p><span style="font-weight: bold;">Installers:</span> {{ $order->client->name }}</p>
      <ul>
        @foreach ($order->installationTeams as $installationTeam)
          <li>{{ $installationTeam->user->name }}</li>
        @endforeach
      </ul>
      <p><span style="font-weight: bold;">City Permits:</span> {{ $order->city_permits ? 'Yes' : 'No' }}</p>
    @endif
    <p>Thank you.</p>
</div>