@php
  use App\Enum\ServiceEnum;
  $mailPhase = $phase ?? null;
  $installationDate = $mailPhase?->installation_date ?? $order->installation_date;
  $installationEndDate = $mailPhase?->installation_end_date ?? $order->installation_end_date;
  $supervisor = $mailPhase?->supervisor ?? $order->supervisor;
  $installationTeams = $mailPhase?->installationTeams ?? $order->installationTeams;
  $phaseProducts = $mailPhase?->phaseProducts ?? collect();
@endphp

<div>
<p>Good day,</p>
<p>I hope this email finds you well. I am writing to provide you with the details of a recent order for your records.</p>
    
    @if ($displaySummary)
    <p style="font-weight: bold;">Order summary</p>
      <p><span style="font-weight: bold;">Delivery Date:</span> {{ \Carbon\Carbon::parse($order->delivery_date)->format('m-d-Y')}}</p>
      @if ($mailPhase)
        <p><span style="font-weight: bold;">Phase:</span> {{ $mailPhase->name }}</p>
      @endif
      <p><span style="font-weight: bold;">Installation Date:</span> {{ $installationDate ? \Carbon\Carbon::parse($installationDate)->format('m-d-Y') : '' }}</p>
      @if ($installationEndDate && $installationEndDate != $installationDate)
        <p><span style="font-weight: bold;">Installation End Date:</span> {{ \Carbon\Carbon::parse($installationEndDate)->format('m-d-Y') }}</p>
      @endif
      @if ($order->service !== ServiceEnum::SERVICE->value)
        <p><span style="font-weight: bold;">Client Pending Payment:</span> {{'$' . number_format($order->cost_delivery  , 2, '.', ',') }}</p>
        <p><span style="font-weight: bold;">City Fee Cost:</span> {{'$' . number_format($order->cost_city_fee , 2, '.', ',')}}</p>
      @endif
      <p><span style="font-weight: bold;">Order Number:</span> {{ $order->order_number }}</p>
      <p><span style="font-weight: bold;">Order Name:</span> {{ $order->name }}</p>
      <p><span style="font-weight: bold;">Client Phone:</span> {{ optional($order->client)->phone ?? '' }}</p>
      <p><span style="font-weight: bold;">Job Address:</span> {{ 
                        ($order->job_address ?? '') .
                        (!empty($order->city) ? ', ' . $order->city : '') .
                        (!empty($order->job_state) ? ', ' . $order->job_state : '') .
                        (!empty($order->job_zip) ? ', ' . $order->job_zip : '') 
                    }}</p>
      <p><span style="font-weight: bold;">Supervisor:</span> {{ optional($supervisor)->name }}</p>
      <p><span style="font-weight: bold;">Seller Name:</span></p>
      <ul>
        @foreach ($order->owners as $owner)
          <li>{{ $owner->name }}</li>
        @endforeach
      </ul>
      <p><span style="font-weight: bold;">Installer:</span></p>
      <ul>
        @foreach ($installationTeams as $installationTeam)
          <li>{{ optional($installationTeam->user)->name }}</li>
        @endforeach
      </ul>
      @if ($mailPhase && !empty($mailPhase->notes))
        <p><span style="font-weight: bold;">Phase Notes:</span> {{ $mailPhase->notes }}</p>
      @endif
      @if ($mailPhase)
        <p><span style="font-weight: bold;">Phase Products:</span></p>
        @if ($phaseProducts->isNotEmpty())
          <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">
            <thead>
              <tr>
                <th align="left">Qty</th>
                <th align="left">Product</th>
                <th align="left">Category</th>
                <th align="left">Config</th>
                <th align="left">Product Notes</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($phaseProducts as $phaseProduct)
                @php
                  $orderProduct = $phaseProduct->orderProduct;
                @endphp
                <tr>
                  <td>{{ number_format((float) $phaseProduct->qty, 2, '.', '') }}</td>
                  <td>{{ optional($orderProduct?->typeOfProduct)->name ?? 'Product' }}</td>
                  <td>{{ optional($orderProduct?->productCategory)->name ?? '' }}</td>
                  <td>{{ optional($orderProduct?->productConfig)->name ?? '' }}</td>
                  <td>{{ $orderProduct?->notes ?? '' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <p>Products: Not assigned to this phase.</p>
        @endif
      @endif
      @if ($order->service !== ServiceEnum::SERVICE->value)
        <p><span style="font-weight: bold;">City Permits:</span> {{ $order->city_permits ? 'Yes' : 'No' }}</p>
      @endif
    @endif
    <p>Thank you.</p>
</div>
