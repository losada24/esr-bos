@php
  $selectedSeller = collect($sellers ?? [])->firstWhere('id', $filters['seller_id'] ?? null);
  $rows = collect($groups ?? [])->flatMap(function ($group) {
    return collect($group['rows'] ?? [])->map(function ($row) use ($group) {
      return array_merge($row, [
        'status' => $group['status'] ?? ($row['status'] ?? null),
        'threshold_label' => $group['threshold_label'] ?? null,
      ]);
    });
  });
@endphp

<table>
  <thead>
    <tr>
      <td colspan="10" style="font-weight: bold; font-size: 16px; background-color: #f0f0f0;">
        Overdue Stage Orders
      </td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Generated At: {{ $generatedAt }}</td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Seller: {{ $selectedSeller['name'] ?? 'All sellers' }}</td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Overdue Only: {{ ($filters['overdue_only'] ?? false) ? 'Yes' : 'No' }}</td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Status Filter: {{ empty($filters['statuses'] ?? []) ? 'All statuses' : implode(', ', $filters['statuses']) }}</td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Order Type Filter: {{ empty($filters['order_types'] ?? []) ? 'All order types' : implode(', ', $filters['order_types']) }}</td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Product Line Filter: {{ empty($filters['product_lines'] ?? []) ? 'All product lines' : implode(', ', $filters['product_lines']) }}</td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Orders: {{ $totals['orders'] }}</td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Overdue Orders: {{ $totals['overdue_orders'] }}</td>
    </tr>
    <tr>
      <td colspan="10" style="font-weight: bold;">Amount: ${{ number_format((float) ($totals['amount'] ?? 0), 2) }}</td>
    </tr>
    <tr>
      <td colspan="10"></td>
    </tr>
    <tr>
      <th>Status</th>
      <th>Order</th>
      <th>Seller</th>
      <th>Created By</th>
      <th>Order Type</th>
      <th>Product Line</th>
      <th>Overdue</th>
      <th>Amount</th>
      <th>Days In Status</th>
      <th>Entered Status At</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($rows as $row)
      <tr style="{{ ($row['is_overdue'] ?? false) ? 'background-color: #fee2e2;' : '' }}">
        <td>{{ $row['status'] ?? '-' }}</td>
        <td>{{ $row['order_label'] ?? '-' }}</td>
        <td>{{ $row['seller_name'] ?? '-' }}</td>
        <td>{{ $row['created_by_name'] ?? '-' }}</td>
        <td>{{ $row['order_type'] ?? '-' }}</td>
        <td>{{ $row['product_line'] ?? '-' }}</td>
        <td>{{ ($row['is_overdue'] ?? false) ? 'Yes' : 'No' }}</td>
        <td>${{ number_format((float) ($row['project_amount'] ?? 0), 2) }}</td>
        <td>{{ $row['days_in_stage'] ?? 0 }}</td>
        <td>{{ $row['stage_entered_at'] ?? '-' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="10">No orders found for the selected filters.</td>
      </tr>
    @endforelse
  </tbody>
</table>
