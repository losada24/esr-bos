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
</style>

<div class="meta">Overdue Stage Orders</div>
<div class="meta">Generated At: {{ $generatedAt }}</div>
<div class="meta">Tracked Statuses: {{ $totals['statuses'] }}</div>
<div class="meta">Configured Statuses: {{ $totals['configured_statuses'] }}</div>
<div class="meta">Overdue Orders: {{ $totals['orders'] }}</div>

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
    @foreach ($group['seller_groups'] as $sellerGroup)
      <div class="group-title">
        {{ $sellerGroup['source'] === 'seller' ? 'Seller' : 'Created By' }}: {{ $sellerGroup['label'] }} ({{ $sellerGroup['count'] }})
      </div>

      <table>
        <thead>
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
