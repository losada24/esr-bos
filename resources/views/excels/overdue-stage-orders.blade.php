<table>
  <thead>
    <tr>
      <td colspan="5" style="font-weight: bold; font-size: 16px; background-color: #f0f0f0;">
        Overdue Stage Orders
      </td>
    </tr>
    <tr>
      <td colspan="5" style="font-weight: bold;">Generated At: {{ $generatedAt }}</td>
    </tr>
    <tr>
      <td colspan="5" style="font-weight: bold;">Seller: {{ $selectedSellerName }}</td>
    </tr>
    <tr>
      <td colspan="5" style="font-weight: bold;">ESR Process Statuses: {{ $totals['statuses'] }}</td>
    </tr>
    <tr>
      <td colspan="5" style="font-weight: bold;">Configured Statuses: {{ $totals['configured_statuses'] }}</td>
    </tr>
    <tr>
      <td colspan="5" style="font-weight: bold;">Orders: {{ $totals['orders'] }}</td>
    </tr>
    <tr>
      <td colspan="5" style="font-weight: bold;">Overdue Orders: {{ $totals['overdue_orders'] }}</td>
    </tr>
    <tr>
      <td colspan="5" style="font-weight: bold;">Amount: ${{ number_format((float) $totals['amount'], 2) }}</td>
    </tr>
  </thead>
</table>

@foreach ($groups as $group)
  <table>
    <thead>
      <tr>
        <td colspan="5" style="font-weight: bold; font-size: 14px; background-color: #f0f0f0;">
          {{ $group['status'] }} ({{ $group['count'] }}) | Threshold: {{ $group['threshold_label'] }}
        </td>
      </tr>
      <tr>
        <td colspan="5" style="font-style: italic;">{{ $group['note'] }}</td>
      </tr>
    </thead>
  </table>

  @if (empty($group['seller_groups']))
    <table>
      <tbody>
        <tr>
          <td colspan="5">
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
          <th>Overdue</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($group['seller_groups'] as $sellerGroup)
          @foreach ($sellerGroup['rows'] as $row)
            <tr>
              <td style="{{ ($row['is_overdue'] ?? false) ? 'background-color: #fde2e2; color: #7f1d1d;' : '' }}">{{ $row['order_label'] ?? '-' }}</td>
              <td style="{{ ($row['is_overdue'] ?? false) ? 'background-color: #fde2e2; color: #7f1d1d;' : '' }}">${{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
              <td style="{{ ($row['is_overdue'] ?? false) ? 'background-color: #fde2e2; color: #7f1d1d;' : '' }}">{{ $row['days_in_stage'] ?? 0 }}</td>
              <td style="{{ ($row['is_overdue'] ?? false) ? 'background-color: #fde2e2; color: #7f1d1d;' : '' }}">{{ $row['order_type'] ?? '-' }}</td>
              <td style="{{ ($row['is_overdue'] ?? false) ? 'background-color: #fde2e2; color: #7f1d1d;' : '' }}">{{ $row['product_line'] ?? '-' }}</td>
              <td style="{{ ($row['is_overdue'] ?? false) ? 'background-color: #fde2e2; color: #7f1d1d;' : '' }}">{{ $row['seller_name'] ?? ($row['created_by_name'] ?? '-') }}</td>
              <td style="{{ ($row['is_overdue'] ?? false) ? 'background-color: #fde2e2; color: #7f1d1d;' : '' }}">{{ $row['stage_entered_at'] ?? '-' }}</td>
              <td style="{{ ($row['is_overdue'] ?? false) ? 'background-color: #fde2e2; color: #7f1d1d;' : '' }}">{{ ($row['is_overdue'] ?? false) ? 'Yes' : 'No' }}</td>
            </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
  @endif
@endforeach
