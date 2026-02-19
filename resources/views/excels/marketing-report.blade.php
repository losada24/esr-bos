<table>
  <thead>
    <tr>
      <td colspan="4" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
        Marketing Report ({{ $startDate }} to {{ $endDate }})
      </td>
    </tr>
    <tr>
      <td colspan="4" style="font-weight: bold;">Total Clients from Sources (Instagram/Facebook + Google Ads): {{ $totals['total_clients'] }}</td>
    </tr>
    <tr>
      <td colspan="4" style="font-weight: bold;">Qualified Clients: {{ $totals['qualified_clients'] }}</td>
    </tr>
    <tr>
      <td colspan="4" style="font-weight: bold;">Qualified Clients with Appointment: {{ $totals['qualified_clients_with_appointment'] }}</td>
    </tr>
    <tr>
      <td colspan="4" style="font-weight: bold;">Lost Request Clients: {{ $totals['lost_clients'] }}</td>
    </tr>
    <tr>
      <td colspan="4" style="font-weight: bold;">Grand Total Clients (Qualified + Lost): {{ $totals['grand_total_clients'] }}</td>
    </tr>
  </thead>
</table>

<table>
  <thead>
    <tr>
      <td colspan="2" style="font-weight: bold;">Qualified Clients by Source</td>
    </tr>
    <tr>
      <th width="30">Source</th>
      <th width="15">Total</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>{{ \App\Enum\ContactSourceEnum::INSTAGRAM_FACEBOOK->value }}</td>
      <td>{{ $totals['qualified_clients_by_source'][\App\Enum\ContactSourceEnum::INSTAGRAM_FACEBOOK->value] ?? 0 }}</td>
    </tr>
    <tr>
      <td>{{ \App\Enum\ContactSourceEnum::GOOGLE_ADS->value }}</td>
      <td>{{ $totals['qualified_clients_by_source'][\App\Enum\ContactSourceEnum::GOOGLE_ADS->value] ?? 0 }}</td>
    </tr>
    <tr>
      <td style="font-weight: bold;">Total</td>
      <td style="font-weight: bold;">
        {{ ($totals['qualified_clients_by_source'][\App\Enum\ContactSourceEnum::INSTAGRAM_FACEBOOK->value] ?? 0) + ($totals['qualified_clients_by_source'][\App\Enum\ContactSourceEnum::GOOGLE_ADS->value] ?? 0) }}
      </td>
    </tr>
  </tbody>
</table>

<table>
  <thead>
    <tr>
      <td colspan="3" style="font-weight: bold;">Qualified Clients With Appointment</td>
    </tr>
    <tr>
      <th width="30">Client</th>
      <th width="20">Source</th>
      <th width="20">Appointment Date</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($qualifiedClientsWithAppointment as $row)
      <tr>
        <td>{{ $row['name'] }}</td>
        <td>{{ $row['source'] }}</td>
        <td>{{ $row['appointment_date'] ?? '-' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="3">No qualified clients with appointment for the selected dates.</td>
      </tr>
    @endforelse
    <tr>
      <td colspan="2" style="font-weight: bold;">Total</td>
      <td style="font-weight: bold;">{{ $totals['qualified_clients_with_appointment'] }}</td>
    </tr>
  </tbody>
</table>

<table>
  <thead>
    <tr>
      <td colspan="2" style="font-weight: bold;">Lost Clients by Reason</td>
    </tr>
    <tr>
      <th width="30">Reason</th>
      <th width="15">Total</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>{{ \App\Enum\LostReasonfrontdeskEnum::DEALER->value }}</td>
      <td>{{ $totals['lost_clients_by_reason'][\App\Enum\LostReasonfrontdeskEnum::DEALER->value] ?? 0 }}</td>
    </tr>
    <tr>
      <td>{{ \App\Enum\LostReasonfrontdeskEnum::STOCK->value }}</td>
      <td>{{ $totals['lost_clients_by_reason'][\App\Enum\LostReasonfrontdeskEnum::STOCK->value] ?? 0 }}</td>
    </tr>
    <tr>
      <td style="font-weight: bold;">Total</td>
      <td style="font-weight: bold;">
        {{ ($totals['lost_clients_by_reason'][\App\Enum\LostReasonfrontdeskEnum::DEALER->value] ?? 0) + ($totals['lost_clients_by_reason'][\App\Enum\LostReasonfrontdeskEnum::STOCK->value] ?? 0) }}
      </td>
    </tr>
  </tbody>
</table>

<table>
  <thead>
    <tr>
      <td colspan="4" style="font-weight: bold;">Qualified Orders by Client</td>
    </tr>
    <tr>
      <th width="30">Client</th>
      <th width="20">Source</th>
      <th width="15">Client Created</th>
      <th width="15">Qualified Orders</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($qualifiedClients as $row)
      <tr>
        <td>{{ $row['name'] }}</td>
        <td>{{ $row['source'] }}</td>
        <td>{{ $row['created_at'] }}</td>
        <td>{{ $row['qualified_orders_count'] }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="4">No qualified clients for the selected dates.</td>
      </tr>
    @endforelse
    <tr>
      <td colspan="3" style="font-weight: bold;">Total Qualified Orders</td>
      <td style="font-weight: bold;">{{ $totals['qualified_orders'] }}</td>
    </tr>
  </tbody>
</table>

<table>
  <thead>
    <tr>
      <td colspan="5" style="font-weight: bold;">Lost Request Clients</td>
    </tr>
    <tr>
      <th width="30">Client</th>
      <th width="20">Source</th>
      <th width="15">Client Created</th>
      <th width="15">Lost Orders</th>
      <th width="25">Loss Reasons</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($lostClients as $row)
      <tr>
        <td>{{ $row['name'] }}</td>
        <td>{{ $row['source'] }}</td>
        <td>{{ $row['created_at'] }}</td>
        <td>{{ $row['lost_orders_count'] }}</td>
        <td>{{ $row['loss_reasons'] ?? '-' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="5">No lost request clients for the selected dates.</td>
      </tr>
    @endforelse
    <tr>
      <td colspan="3" style="font-weight: bold;">Total</td>
      <td style="font-weight: bold;">{{ $totals['lost_orders'] }}</td>
      <td></td>
    </tr>
  </tbody>
</table>
