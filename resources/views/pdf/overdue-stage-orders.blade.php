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
  .group-title {
    margin-top: 10px;
    margin-bottom: 6px;
    font-weight: bold;
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
  .overdue-row td {
    background-color: #fde2e2;
    color: #7f1d1d;
  }
</style>

<div class="meta">Overdue Stage Orders</div>
<div class="meta">Generated At: {{ $generatedAt }}</div>
<div class="meta">Seller: {{ $selectedSellerName }}</div>
<div class="meta">ESR Process Statuses: {{ $totals['statuses'] }}</div>
<div class="meta">Configured Statuses: {{ $totals['configured_statuses'] }}</div>
<div class="meta">Orders: {{ $totals['orders'] }}</div>
<div class="meta">Overdue Orders: {{ $totals['overdue_orders'] }}</div>
<div class="meta">Amount: ${{ number_format((float) $totals['amount'], 2) }}</div>

@foreach ($groups as $group)
  <div class="status-title">
    {{ $group['status'] }} ({{ $group['count'] }}) | Threshold: {{ $group['threshold_label'] }}
  </div>
  <div>{{ $group['note'] }}</div>

  @if (empty($group['seller_groups']))
    <table>
      <tbody>
        <tr>
          <td>
            {{ $group['is_configured']
              ? 'No overdue orders in this status.'
              : 'No overdue evaluation is available for this status because it has no threshold configured.' }}
          </td>
        </tr>
      </tbody>
    </table>
  @else
    <table>
      <thead>
        <tr>
          <th>Order</th>
          <th>Amount</th>
          <th>Days In Status</th>
          <th>Order Type</th>
          <th>Product Line</th>
          <th>Seller</th>
          <th>Entered Status At</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($group['seller_groups'] as $sellerGroup)
          @foreach ($sellerGroup['rows'] as $row)
            <tr class="{{ ($row['is_overdue'] ?? false) ? 'overdue-row' : '' }}">
              <td>{{ $row['order_label'] ?? '-' }}</td>
              <td>${{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
              <td>{{ $row['days_in_stage'] ?? 0 }}</td>
              <td>{{ $row['order_type'] ?? '-' }}</td>
              <td>{{ $row['product_line'] ?? '-' }}</td>
              <td>{{ $row['seller_name'] ?? ($row['created_by_name'] ?? '-') }}</td>
              <td>{{ $row['stage_entered_at'] ?? '-' }}</td>
            </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
  @endif
@endforeach
