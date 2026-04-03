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
      <td colspan="5" style="font-weight: bold;">Tracked Statuses: {{ $totals['statuses'] }}</td>
    </tr>
    <tr>
      <td colspan="5" style="font-weight: bold;">Configured Statuses: {{ $totals['configured_statuses'] }}</td>
    </tr>
    <tr>
      <td colspan="5" style="font-weight: bold;">Overdue Orders: {{ $totals['orders'] }}</td>
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
    @foreach ($group['seller_groups'] as $sellerGroup)
      <table>
        <thead>
          <tr>
            <td colspan="5" style="font-weight: bold; background-color: #fafafa;">
              {{ $sellerGroup['source'] === 'seller' ? 'Seller' : 'Created By' }}: {{ $sellerGroup['label'] }} ({{ $sellerGroup['count'] }})
            </td>
          </tr>
          <tr>
            <th>Order</th>
            <th>{{ $sellerGroup['source'] === 'seller' ? 'Seller' : 'Created By' }}</th>
            <th>Days In Stage</th>
            <th>Created At</th>
            <th>Entered Stage At</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($sellerGroup['rows'] as $row)
            <tr>
              <td>{{ $row['order_label'] ?? '-' }}</td>
              <td>{{ $sellerGroup['source'] === 'seller' ? ($row['seller_name'] ?? '-') : ($row['created_by_name'] ?? '-') }}</td>
              <td>{{ $row['days_in_stage'] ?? 0 }}</td>
              <td>{{ $row['created_at'] ?? '-' }}</td>
              <td>{{ $row['stage_entered_at'] ?? '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endforeach
  @endif
@endforeach
