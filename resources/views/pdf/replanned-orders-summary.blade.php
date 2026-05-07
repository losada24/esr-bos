<style>
  body {
    font-family: Arial, sans-serif;
    font-size: 12px;
  }
  .meta {
    margin-bottom: 6px;
    font-weight: bold;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
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

<div class="meta">Replanned Orders Summary</div>
<div class="meta">Range: {{ $startDate }} to {{ $endDate }}</div>
<div class="meta">Total Replanned: {{ $totals['total'] }}</div>

<table>
  <thead>
    <tr>
      <th>Reason</th>
      <th>Count</th>
    </tr>
  </thead>
  <tbody>
    @forelse (($totals['reason_counts'] ?? []) as $reason => $count)
      <tr>
        <td>{{ $reason }}</td>
        <td>{{ $count }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="2">No replanned reasons found for the selected dates.</td>
      </tr>
    @endforelse
  </tbody>
  <tfoot>
    <tr class="totals">
      <td>Total</td>
      <td>{{ $totals['total'] }}</td>
    </tr>
  </tfoot>
</table>

<table>
  <thead>
    <tr>
      <th>Order #</th>
      <th>Order Name</th>
      <th>Replanned At</th>
      <th>Replanned Reasons</th>
      <th>Planned Pickup</th>
      <th>Planned Start</th>
      <th>Planned End</th>
      <th>Replanned Pickup</th>
      <th>Replanned Start</th>
      <th>Replanned End</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($rows as $row)
      <tr>
        <td>{{ $row['order_number'] ?: '#' . $row['order_id'] }}</td>
        <td>{{ $row['order_name'] ?: '-' }}</td>
        <td>{{ $row['replanned_at'] ?: '-' }}</td>
        <td>{{ $row['replanned_reasons_label'] ?: '-' }}</td>
        <td>{{ $row['planned_pickup_date'] ?: '-' }}</td>
        <td>{{ $row['planned_start_date'] ?: '-' }}</td>
        <td>{{ $row['planned_end_date'] ?: '-' }}</td>
        <td>{{ $row['replanned_pickup_date'] ?: '-' }}</td>
        <td>{{ $row['replanned_start_date'] ?: '-' }}</td>
        <td>{{ $row['replanned_end_date'] ?: '-' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="10">No replanned orders found for the selected dates.</td>
      </tr>
    @endforelse
  </tbody>
</table>
