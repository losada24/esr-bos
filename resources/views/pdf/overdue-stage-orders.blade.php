<style>
  body {
    font-family: Arial, sans-serif;
    font-size: 11px;
  }
  .meta {
    margin-bottom: 6px;
    font-weight: bold;
  }
  .status-title {
    margin-top: 16px;
    margin-bottom: 8px;
    font-weight: bold;
    font-size: 13px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
  }
  th, td {
    border: 1px solid #000;
    padding: 5px;
    text-align: left;
    vertical-align: top;
  }
  th {
    background-color: #f0f0f0;
    font-weight: bold;
  }
  .overdue {
    background-color: #fee2e2;
  }
</style>

@php
  $selectedSeller = collect($sellers ?? [])->firstWhere('id', $filters['seller_id'] ?? null);
@endphp

<div class="meta">Overdue Stage Orders</div>
<div class="meta">Generated At: {{ $generatedAt }}</div>
<div class="meta">Seller: {{ $selectedSeller['name'] ?? 'All sellers' }}</div>
<div class="meta">Overdue Only: {{ ($filters['overdue_only'] ?? false) ? 'Yes' : 'No' }}</div>
<div class="meta">Status Filter: {{ empty($filters['statuses'] ?? []) ? 'All statuses' : implode(', ', $filters['statuses']) }}</div>
<div class="meta">Order Type Filter: {{ empty($filters['order_types'] ?? []) ? 'All order types' : implode(', ', $filters['order_types']) }}</div>
<div class="meta">Product Line Filter: {{ empty($filters['product_lines'] ?? []) ? 'All product lines' : implode(', ', $filters['product_lines']) }}</div>
<div class="meta">Statuses: {{ $totals['statuses'] }}</div>
<div class="meta">Orders: {{ $totals['orders'] }}</div>
<div class="meta">Overdue Orders: {{ $totals['overdue_orders'] }}</div>
<div class="meta">Amount: ${{ number_format((float) ($totals['amount'] ?? 0), 2) }}</div>

@foreach ($groups as $group)
  <div class="status-title">
    {{ $group['status'] }} ({{ $group['count'] }}) |
    Overdue: {{ $group['overdue_count'] }} |
    Threshold: {{ $group['threshold_label'] }} |
    Total: ${{ number_format((float) ($group['amount_total'] ?? 0), 2) }}
  </div>
  <div>{{ $group['note'] }}</div>

  <table>
    <thead>
      <tr>
        <th>Order</th>
        <th>Amount</th>
        <th>Days In Status</th>
        <th>Seller</th>
        <th>Entered Status At</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($group['rows'] as $row)
        <tr class="{{ ($row['is_overdue'] ?? false) ? 'overdue' : '' }}">
          <td>{{ $row['order_label'] ?? '-' }}</td>
          <td>${{ number_format((float) ($row['project_amount'] ?? 0), 2) }}</td>
          <td>{{ $row['days_in_stage'] ?? 0 }}</td>
          <td>{{ $row['seller_name'] ?? '-' }}</td>
          <td>{{ $row['stage_entered_at'] ?? '-' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5">No orders found in this status.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
@endforeach
