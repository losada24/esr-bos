@php
  $appName = config('app.name');
  $visibleGroups = collect($groups ?? [])->filter(fn ($group) => (int) ($group['count'] ?? 0) > 0)->values();
@endphp

<table>
  <thead>
    <tr>
      <td colspan="14">{{ $appName }} | Overdue Stage Orders Report</td>
    </tr>
    <tr>
      <td colspan="14">Generated At: {{ $generatedAt }}</td>
    </tr>
    <tr>
      <td>Statuses</td>
      <td>{{ (int) ($totals['configured_statuses'] ?? $totals['statuses'] ?? 0) }}</td>
      <td>Overdue Orders</td>
      <td>{{ (int) ($totals['overdue_orders'] ?? 0) }}</td>
      <td>Amount</td>
      <td>{{ (float) ($totals['amount'] ?? 0) }}</td>
      <td colspan="8">Seller: {{ $selectedSellerName ?? 'All sellers' }}</td>
    </tr>
    <tr>
      <td colspan="14"></td>
    </tr>
    <tr>
      <th>Row Type</th>
      <th>Status</th>
      <th>Seller</th>
      <th>Order</th>
      <th>Amount</th>
      <th>Days In Status</th>
      <th>Order Type</th>
      <th>Product Line</th>
      <th>Entered Status At</th>
      <th>Extension Status</th>
      <th>Extension Business Days</th>
      <th>Extension Until</th>
      <th>Extension User</th>
      <th>Extension Note</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($visibleGroups as $group)
      @php
        $sellerGroups = collect($group['seller_groups'] ?? [])
          ->filter(fn ($sellerGroup) => (int) ($sellerGroup['count'] ?? 0) > 0)
          ->values();
      @endphp

      <tr>
        <td>Status Total</td>
        <td>{{ $group['status'] ?? '-' }}</td>
        <td></td>
        <td>{{ number_format((int) ($group['count'] ?? 0)) }} orders</td>
        <td>{{ (float) ($group['amount'] ?? 0) }}</td>
        <td>{{ $group['threshold_label'] ?? '-' }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>

      @foreach ($sellerGroups as $sellerGroup)
        @php
          $sellerRows = collect($sellerGroup['rows'] ?? []);
        @endphp
        <tr>
          <td>Seller Total</td>
          <td>{{ $group['status'] ?? '-' }}</td>
          <td>{{ $sellerGroup['label'] ?? '-' }}</td>
          <td>{{ number_format((int) ($sellerGroup['count'] ?? 0)) }} orders</td>
          <td>{{ (float) $sellerRows->sum('amount') }}</td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>

        @foreach ($sellerRows as $row)
          <tr>
            <td>Order</td>
            <td>{{ $row['status'] ?? ($group['status'] ?? '-') }}</td>
            <td>{{ $sellerGroup['label'] ?? ($row['seller_name'] ?? ($row['created_by_name'] ?? '-')) }}</td>
            <td>{{ $row['order_label'] ?? '-' }}</td>
            <td>{{ (float) ($row['amount'] ?? 0) }}</td>
            <td>{{ $row['days_in_stage'] ?? 0 }}</td>
            <td>{{ $row['order_type'] ?? '-' }}</td>
            <td>{{ $row['product_line'] ?? '-' }}</td>
            <td>{{ $row['stage_entered_at'] ?? '-' }}</td>
            <td>{{ !empty($row['overdue_extension']) ? (!empty($row['overdue_extension_active']) ? 'Active' : 'Last') : '' }}</td>
            <td>{{ $row['overdue_extension']['business_days'] ?? '' }}</td>
            <td>{{ $row['overdue_extension']['extended_until'] ?? '' }}</td>
            <td>{{ $row['overdue_extension']['user']['name'] ?? '' }}</td>
            <td>{{ $row['overdue_extension']['note'] ?? '' }}</td>
          </tr>
        @endforeach
      @endforeach
    @empty
      <tr>
        <td colspan="14">No matching orders were found for this report.</td>
      </tr>
    @endforelse
  </tbody>
</table>
