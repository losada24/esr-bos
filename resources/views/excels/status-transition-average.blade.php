<table>
  <thead>
    <tr>
      <td colspan="9" style="font-weight: bold; font-size: 16px; background-color: #f0f0f0;">
        Status Transition Average
      </td>
    </tr>
    <tr>
      <td colspan="9" style="font-weight: bold;">Range: {{ $startDate }} to {{ $endDate }}</td>
    </tr>
    <tr>
      <td colspan="9" style="font-weight: bold;">Transition: {{ $transitionLabel }}</td>
    </tr>
    <tr>
      <td colspan="9" style="font-weight: bold;">Type: {{ $businessTypeLabel }}</td>
    </tr>
    <tr>
      <td colspan="9" style="font-weight: bold;">Service: {{ $serviceTypeLabel }}</td>
    </tr>
    <tr>
      <td colspan="9" style="font-weight: bold;">Orders: {{ $totalOrders }}</td>
    </tr>
    <tr>
      <td colspan="9" style="font-weight: bold;">Average: {{ $averageDurationDays }} days ({{ $averageDurationLabel }})</td>
    </tr>
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
        <td>{{ $row['duration_days'] }}</td>
        <td>{{ $row['duration_label'] }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="9">No orders found for selected filters.</td>
      </tr>
    @endforelse
  </tbody>
</table>
