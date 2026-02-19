<style>
  body {
    font-family: Arial, sans-serif;
    font-size: 12px;
  }
  .section-title {
    font-weight: bold;
    font-size: 14px;
    margin: 16px 0 6px;
  }
  .meta {
    font-weight: bold;
    margin-bottom: 6px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
  }
  th, td {
    border: 1px solid #000;
    padding: 6px;
    text-align: left;
    vertical-align: middle;
  }
  th {
    background-color: #f0f0f0;
    font-weight: bold;
  }
  .totals {
    font-weight: bold;
    background-color: #f7f7f7;
  }
</style>

<div class="meta">Marketing Report ({{ $startDate }} to {{ $endDate }})</div>
<div class="meta">Total Clients from Sources (Instagram/Facebook + Google Ads): {{ $totals['total_clients'] }}</div>
<div class="meta">Qualified Clients: {{ $totals['qualified_clients'] }}</div>
<div class="meta">Qualified Clients with Appointment: {{ $totals['qualified_clients_with_appointment'] }}</div>
<div class="meta">Lost Request Clients: {{ $totals['lost_clients'] }}</div>
<div class="meta">Grand Total Clients (Qualified + Lost): {{ $totals['grand_total_clients'] }}</div>

<table>
  <thead>
    <tr class="totals">
      <td colspan="2">Qualified Clients by Source</td>
    </tr>
    <tr>
      <th>Source</th>
      <th>Total</th>
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
    <tr class="totals">
      <td>Total</td>
      <td>{{ ($totals['qualified_clients_by_source'][\App\Enum\ContactSourceEnum::INSTAGRAM_FACEBOOK->value] ?? 0) + ($totals['qualified_clients_by_source'][\App\Enum\ContactSourceEnum::GOOGLE_ADS->value] ?? 0) }}</td>
    </tr>
  </tbody>
</table>

<div class="section-title">Qualified Clients With Appointment</div>
<table>
  <thead>
    <tr>
      <th>Client</th>
      <th>Source</th>
      <th>Appointment Date</th>
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
    <tr class="totals">
      <td colspan="2">Total</td>
      <td>{{ $totals['qualified_clients_with_appointment'] }}</td>
    </tr>
  </tbody>
</table>

<table>
  <thead>
    <tr class="totals">
      <td colspan="2">Lost Clients by Reason</td>
    </tr>
    <tr>
      <th>Reason</th>
      <th>Total</th>
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
    <tr class="totals">
      <td>Total</td>
      <td>{{ ($totals['lost_clients_by_reason'][\App\Enum\LostReasonfrontdeskEnum::DEALER->value] ?? 0) + ($totals['lost_clients_by_reason'][\App\Enum\LostReasonfrontdeskEnum::STOCK->value] ?? 0) }}</td>
    </tr>
  </tbody>
</table>

<div class="section-title">Qualified Orders by Client</div>
<table>
  <thead>
    <tr>
      <th>Client</th>
      <th>Source</th>
      <th>Client Created</th>
      <th>Qualified Orders</th>
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
    <tr class="totals">
      <td colspan="3">Total Qualified Orders</td>
      <td>{{ $totals['qualified_orders'] }}</td>
    </tr>
  </tbody>
</table>

<div class="section-title">Lost Request Clients</div>
<table>
  <thead>
    <tr>
      <th>Client</th>
      <th>Source</th>
      <th>Client Created</th>
      <th>Lost Orders</th>
      <th>Loss Reasons</th>
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
    <tr class="totals">
      <td colspan="3">Total</td>
      <td>{{ $totals['lost_orders'] }}</td>
      <td></td>
    </tr>
  </tbody>
</table>
