<table>
  <thead>
    <tr>
      <td colspan="10" style="font-weight: bold; font-size: 16px; background-color: #f0f0f0;">
        Replanned Orders Summary
      </td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Range: {{ $startDate }} to {{ $endDate }}</td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Total Replanned: {{ $totals['total'] }}</td>
    </tr>
    <tr>
      <td colspan="10"></td>
    </tr>
    <tr>
      <th colspan="2">Reason Counts</th>
      <th colspan="8"></th>
    </tr>
    <tr>
      <th>Reason</th>
      <th>Count</th>
      <th colspan="8"></th>
    </tr>
    @forelse (($totals['reason_counts'] ?? []) as $reason => $count)
      <tr>
        <td>{{ $reason }}</td>
        <td>{{ $count }}</td>
        <td colspan="8"></td>
      </tr>
    @empty
      <tr>
        <td colspan="10">No replanned reasons found for the selected dates.</td>
      </tr>
    @endforelse
    <tr>
      <td colspan="10"></td>
    </tr>
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
