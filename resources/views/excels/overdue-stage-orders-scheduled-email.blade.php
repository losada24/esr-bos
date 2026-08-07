<table>
  <thead>
    <tr>
      <td colspan="9" style="font-weight: bold; font-size: 18px; background-color: #2563eb; color: #ffffff;">
        Overdue Stage Orders Report
      </td>
    </tr>
    <tr>
      <td colspan="9" style="font-weight: bold; background-color: #dbeafe;">Generated At: {{ $generatedAt }}</td>
    </tr>
    <tr>
      <td style="font-weight: bold; background-color: #f3f4f6;">Statuses</td>
      <td style="font-weight: bold;">{{ $totals['statuses'] ?? 0 }}</td>
      <td style="font-weight: bold; background-color: #f3f4f6;">Overdue Orders</td>
      <td style="font-weight: bold;">{{ $totals['overdue_orders'] ?? 0 }}</td>
      <td style="font-weight: bold; background-color: #f3f4f6;">Amount</td>
      <td style="font-weight: bold;">{{ (float) ($totals['amount'] ?? 0) }}</td>
      <td colspan="3"></td>
    </tr>
    <tr>
      <td colspan="9"></td>
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
    </tr>
  </thead>
  <tbody>
    @forelse ($groups as $group)
      <tr>
        <td>Status Total</td>
        <td>{{ $group['status'] }}</td>
        <td></td>
        <td>{{ $group['count'] }} orders</td>
        <td>{{ (float) ($group['amount_total'] ?? 0) }}</td>
        <td></td>
        <td>{{ $group['threshold_label'] ?? '' }}</td>
        <td></td>
        <td></td>
      </tr>

      @foreach (($group['seller_groups'] ?? []) as $sellerGroup)
        <tr>
          <td>Seller Total</td>
          <td>{{ $group['status'] }}</td>
          <td>{{ $sellerGroup['seller_name'] }}</td>
          <td>{{ $sellerGroup['count'] }} orders</td>
          <td>{{ (float) ($sellerGroup['amount_total'] ?? 0) }}</td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>

        @foreach (($sellerGroup['rows'] ?? []) as $row)
          <tr>
            <td>Order</td>
            <td>{{ $group['status'] }}</td>
            <td>{{ $sellerGroup['seller_name'] }}</td>
            <td>{{ $row['order_label'] ?? '-' }}</td>
            <td>{{ (float) ($row['project_amount'] ?? 0) }}</td>
            <td>{{ $row['days_in_stage'] ?? 0 }}</td>
            <td>{{ $row['order_type'] ?? '-' }}</td>
            <td>{{ $row['product_line'] ?? '-' }}</td>
            <td>{{ $row['stage_entered_at'] ?? '-' }}</td>
          </tr>
        @endforeach
      @endforeach
    @empty
      <tr>
        <td>No Orders</td>
        <td colspan="8">No overdue orders found.</td>
      </tr>
    @endforelse
    <tr>
      <td>Total</td>
      <td></td>
      <td></td>
      <td>{{ $totals['overdue_orders'] ?? 0 }} orders</td>
      <td>{{ (float) ($totals['amount'] ?? 0) }}</td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
  </tbody>
</table>
