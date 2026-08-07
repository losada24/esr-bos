<style>
  @page {
    margin: 20px 22px;
  }
  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 14px;
    color: #111827;
  }
  .header {
    background: #2563eb;
    color: #ffffff;
    padding: 18px 20px;
    border-radius: 8px;
    margin-bottom: 14px;
  }
  .eyebrow {
    font-size: 13px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #dbeafe;
  }
  .title {
    font-size: 28px;
    font-weight: bold;
    margin-top: 5px;
  }
  .generated {
    font-size: 14px;
    margin-top: 6px;
    color: #dbeafe;
  }
  .summary {
    width: 100%;
    border-collapse: separate;
    border-spacing: 8px 0;
    margin-bottom: 12px;
  }
  .summary td {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 11px 12px;
    background: #f9fafb;
  }
  .metric-label {
    font-size: 12px;
    text-transform: uppercase;
    font-weight: bold;
    color: #6b7280;
  }
  .metric-value {
    font-size: 24px;
    font-weight: bold;
    margin-top: 4px;
    color: #111827;
  }
  .empty {
    border: 1px solid #bbf7d0;
    background: #f0fdf4;
    color: #166534;
    padding: 14px;
    border-radius: 8px;
    font-weight: bold;
  }
  .status-block {
    page-break-inside: avoid;
    margin-top: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
  }
  .status-header {
    background: #f3f4f6;
    padding: 10px 12px;
    border-bottom: 1px solid #e5e7eb;
  }
  .status-name {
    font-size: 17px;
    font-weight: bold;
  }
  .status-meta {
    font-size: 13px;
    color: #4b5563;
    margin-top: 3px;
  }
  .seller-header {
    padding: 9px 12px;
    background: #fff7ed;
    border-top: 1px solid #fed7aa;
    border-bottom: 1px solid #fed7aa;
    color: #7c2d12;
    font-weight: bold;
    font-size: 14px;
  }
  table.orders {
    width: 100%;
    border-collapse: collapse;
  }
  table.orders th,
  table.orders td {
    border-bottom: 1px solid #e5e7eb;
    padding: 7px 8px;
    text-align: left;
    vertical-align: top;
    font-size: 13px;
  }
  table.orders th {
    background: #ffffff;
    color: #374151;
    font-size: 12px;
    text-transform: uppercase;
  }
  table.orders tr.overdue td {
    background: #fee2e2;
    color: #111827;
  }
  .amount {
    text-align: right;
    white-space: nowrap;
  }
  .nowrap {
    white-space: nowrap;
  }
</style>

<div class="header">
  <div class="eyebrow">Scheduled Report</div>
  <div class="title">Overdue Stage Orders</div>
  <div class="generated">Generated at {{ $generatedAt }}</div>
</div>

<table class="summary">
  <tr>
    <td>
      <div class="metric-label">Statuses</div>
      <div class="metric-value">{{ $totals['statuses'] ?? 0 }}</div>
    </td>
    <td>
      <div class="metric-label">Overdue Orders</div>
      <div class="metric-value">{{ $totals['overdue_orders'] ?? 0 }}</div>
    </td>
    <td>
      <div class="metric-label">Amount</div>
      <div class="metric-value">${{ number_format((float) ($totals['amount'] ?? 0), 2) }}</div>
    </td>
  </tr>
</table>

@if (($totals['overdue_orders'] ?? 0) === 0)
  <div class="empty">No overdue orders found.</div>
@endif

@foreach ($groups as $group)
  <div class="status-block">
    <div class="status-header">
      <div class="status-name">{{ $group['status'] }}</div>
      <div class="status-meta">
        {{ $group['count'] }} overdue orders |
        {{ $group['threshold_label'] }} |
        ${{ number_format((float) ($group['amount_total'] ?? 0), 2) }}
      </div>
    </div>

    @foreach (($group['seller_groups'] ?? []) as $sellerGroup)
      <div class="seller-header">
        {{ $sellerGroup['seller_name'] }} |
        {{ $sellerGroup['count'] }} orders |
        ${{ number_format((float) ($sellerGroup['amount_total'] ?? 0), 2) }}
      </div>
      <table class="orders">
        <thead>
          <tr>
            <th width="32%">Order</th>
            <th width="12%">Amount</th>
            <th width="10%">Days</th>
            <th width="14%">Order Type</th>
            <th width="14%">Product Line</th>
            <th width="18%">Entered Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach (($sellerGroup['rows'] ?? []) as $row)
            <tr class="overdue">
              <td>{{ $row['order_label'] ?? '-' }}</td>
              <td class="amount">${{ number_format((float) ($row['project_amount'] ?? 0), 2) }}</td>
              <td class="nowrap">{{ $row['days_in_stage'] ?? 0 }}</td>
              <td>{{ $row['order_type'] ?? '-' }}</td>
              <td>{{ $row['product_line'] ?? '-' }}</td>
              <td class="nowrap">{{ $row['stage_entered_at'] ?? '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endforeach
  </div>
@endforeach
