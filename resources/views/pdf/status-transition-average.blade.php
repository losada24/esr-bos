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
  .right {
    text-align: right;
  }
</style>

<div class="meta">Status Transition Average</div>
<div class="meta">Range: {{ $startDate }} to {{ $endDate }}</div>
<div class="meta">Transition: {{ $transitionLabel }}</div>
<div class="meta">Type: {{ $businessTypeLabel }}</div>
<div class="meta">Service: {{ $serviceTypeLabel }}</div>
<div class="meta">Orders: {{ $totalOrders }}</div>
<div class="meta">Average: {{ $averageDurationDays }} days ({{ $averageDurationLabel }})</div>

<table>
  <thead>
    <tr>
      <th>Order #</th>
      <th>Order Name</th>
      <th>Service</th>
      <th>Order Type</th>
      <th>Type of Housing</th>
      <th>{{ $startStatusLabel }}</th>
      <th>Completed At</th>
      <th>Duration (Days)</th>
      <th>Duration</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($rows as $row)
      <tr>
        <td>{{ $row['order_number'] ?? '-' }}</td>
        <td>{{ $row['name'] }}</td>
        <td>{{ $row['service'] ?? '-' }}</td>
        <td>{{ $row['order_type'] ?? '-' }}</td>
        <td>{{ $row['type_of_housing'] ?? '-' }}</td>
        <td>{{ $row['start_at'] }}</td>
        <td>{{ $row['completed_at'] }}</td>
        <td class="right">{{ $row['duration_days'] }}</td>
        <td>{{ $row['duration_label'] }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="9">No orders found for selected filters.</td>
      </tr>
    @endforelse
  </tbody>
</table>
