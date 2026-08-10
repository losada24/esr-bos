@php
  $statusCount = (int) ($totals['configured_statuses'] ?? $totals['statuses'] ?? 0);
  $orderCount = (int) ($totals['orders'] ?? 0);
  $overdueOrders = (int) ($totals['overdue_orders'] ?? 0);
  $amount = (float) ($totals['amount'] ?? 0);
  $visibleGroups = collect($groups ?? [])->filter(fn ($group) => (int) ($group['count'] ?? 0) > 0)->values();
  $appName = config('app.name');
@endphp

<style>
  @page {
    margin: 14px;
  }

  body {
    margin: 0;
    background: #ffffff;
    color: #1f2937;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10px;
  }

  .page {
    background: #ffffff;
  }

  .hero {
    background: #2f63df;
    border-radius: 6px;
    color: #ffffff;
    padding: 16px 18px 18px;
  }

  .eyebrow {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.1px;
    margin-bottom: 8px;
    text-transform: uppercase;
  }

  .title {
    font-size: 24px;
    font-weight: 700;
    line-height: 28px;
    margin-bottom: 8px;
  }

  .generated {
    font-size: 12px;
    line-height: 16px;
  }

  .content {
    padding: 10px 0 0;
  }

  .summary-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
  }

  .summary-gap {
    width: 6px;
  }

  .summary-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 11px 10px 9px;
  }

  .summary-label {
    color: #6b7280;
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 7px;
    text-transform: uppercase;
  }

  .summary-value {
    color: #111827;
    font-size: 20px;
    font-weight: 700;
    line-height: 24px;
  }

  .note {
    color: #374151;
    font-size: 11px;
    line-height: 16px;
    margin: 10px 0 9px;
  }

  .status-block {
    background: #f3f4f6;
    border-radius: 6px 6px 0 0;
    margin-top: 9px;
    overflow: hidden;
    page-break-inside: avoid;
  }

  .status-title {
    color: #111827;
    font-size: 14px;
    font-weight: 700;
    line-height: 18px;
    padding: 10px 9px 3px;
    text-transform: uppercase;
  }

  .status-meta {
    color: #667085;
    font-size: 11px;
    line-height: 15px;
    padding: 0 9px 8px;
  }

  .seller-block {
    background: #ffffff;
    border-top: 1px solid #fed7aa;
    page-break-inside: avoid;
  }

  .seller-title {
    background: #fff7ed;
    color: #9a3412;
    font-size: 11px;
    font-weight: 700;
    line-height: 15px;
    padding: 8px 9px;
  }

  .detail-table {
    border-collapse: collapse;
    margin: 0;
    width: 100%;
  }

  .detail-table th,
  .detail-table td {
    border-bottom: 1px solid #e5e7eb;
    padding: 6px 8px;
    text-align: left;
    vertical-align: top;
  }

  .detail-table th {
    background: #f8fafc;
    color: #334155;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
  }

  .detail-table td {
    color: #111827;
    font-size: 9px;
    font-weight: 600;
  }

  .overdue-row td {
    background: #fee2e2;
    color: #7f1d1d;
  }

  .empty {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    color: #667085;
    font-size: 13px;
    padding: 18px 20px;
  }
</style>

<div class="page">
  <div class="hero">
    <div class="eyebrow">{{ $appName }} | Scheduled Report</div>
    <div class="title">Overdue Stage Orders</div>
    <div class="generated">Generated at {{ $generatedAt }}</div>
  </div>

  <div class="content">
    <table class="summary-table">
      <tr>
        <td class="summary-card statuses-card" width="31%">
          <div class="summary-label">Statuses</div>
          <div class="summary-value">{{ number_format($statusCount) }}</div>
        </td>
        <td class="summary-gap"></td>
        <td class="summary-card overdue-card" width="31%">
          <div class="summary-label">Overdue Orders</div>
          <div class="summary-value">{{ number_format($overdueOrders) }}</div>
        </td>
        <td class="summary-gap"></td>
        <td class="summary-card amount-card" width="32%">
          <div class="summary-label">Amount</div>
          <div class="summary-value">${{ number_format($amount, 2) }}</div>
        </td>
      </tr>
    </table>

    @forelse ($visibleGroups as $group)
      @php
        $sellerGroups = collect($group['seller_groups'] ?? [])
          ->filter(fn ($sellerGroup) => (int) ($sellerGroup['count'] ?? 0) > 0)
          ->values();
      @endphp

      <div class="status-block">
        <div class="status-title">{{ $group['status'] ?? 'Status' }}</div>
        <div class="status-meta">
          {{ number_format((int) ($group['overdue_count'] ?? 0)) }} overdue orders |
          {{ $group['threshold_label'] ?? 'Not configured' }} |
          ${{ number_format((float) ($group['amount'] ?? 0), 2) }}
        </div>

        @foreach ($sellerGroups as $sellerGroup)
          <div class="seller-block">
            <div class="seller-title">
              {{ $sellerGroup['label'] ?? 'Seller' }} |
              {{ number_format((int) ($sellerGroup['count'] ?? 0)) }} orders |
              ${{ number_format((float) collect($sellerGroup['rows'] ?? [])->sum('amount'), 2) }}
            </div>

            <table class="detail-table">
              <thead>
                <tr>
                  <th width="32%">Order</th>
                  <th width="12%">Amount</th>
                  <th width="10%">Days</th>
                  <th width="16%">Order Type</th>
                  <th width="16%">Product Line</th>
                  <th width="14%">Entered Status</th>
                </tr>
              </thead>
              <tbody>
              @foreach (($sellerGroup['rows'] ?? []) as $row)
                <tr class="{{ ($row['is_overdue'] ?? false) ? 'overdue-row' : '' }}">
                  <td>{{ $row['order_label'] ?? '-' }}</td>
                  <td>${{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                  <td>{{ $row['days_in_stage'] ?? 0 }}</td>
                  <td>{{ $row['order_type'] ?? '-' }}</td>
                  <td>{{ $row['product_line'] ?? '-' }}</td>
                  <td>{{ $row['stage_entered_at'] ?? '-' }}</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        @endforeach
      </div>
    @empty
      <div class="empty">No matching orders were found for this report.</div>
    @endforelse
  </div>
</div>
