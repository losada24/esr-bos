<table>
    <thead>
      <tr>
        <td colspan="4" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
            Daily Order Status ({{ $startDate }} to {{ $endDate }})
        </td>
      </tr>
      <tr>
        <td colspan="4" style="font-weight: bold;">Total: {{ $totals['total'] }}</td>
      </tr>
      <tr>
        <td colspan="4" style="font-weight: bold;">Total Orders: {{ $totals['total_orders'] ?? count($orderLists['total'] ?? []) }}</td>
      </tr>
      <tr>
        <td colspan="4" style="font-weight: bold;">Total Qualified: {{ $totals['qualified'] }}</td>
      </tr>
      <tr>
        <td colspan="4" style="font-weight: bold;">Total Estimate &amp; Appt Schedule: {{ $totals['estimate_appt_schedule'] }}</td>
      </tr>
      <tr>
        <td colspan="4"><strong>Total Orders List:</strong> {{ collect($orderLists['total'] ?? [])->map(fn ($order) => ($order['name'] ? '#' . $order['id'] . ' - ' . $order['name'] : '#' . $order['id']) . ' | ' . ($order['created_date'] ?? '-'))->implode(', ') ?: 'No orders for the selected dates.' }}</td>
      </tr>
      <tr>
        <td colspan="4"><strong>Qualified Orders List:</strong> {{ collect($orderLists['qualified'] ?? [])->map(fn ($order) => ($order['name'] ? '#' . $order['id'] . ' - ' . $order['name'] : '#' . $order['id']) . ' | ' . ($order['created_date'] ?? '-'))->implode(', ') ?: 'No qualified orders for the selected dates.' }}</td>
      </tr>
      <tr>
        <td colspan="4"><strong>Estimate &amp; Appt Schedule Orders List:</strong> {{ collect($orderLists['estimate_appt_schedule'] ?? [])->map(fn ($order) => ($order['name'] ? '#' . $order['id'] . ' - ' . $order['name'] : '#' . $order['id']) . ' | ' . ($order['created_date'] ?? '-'))->implode(', ') ?: 'No estimate & appt schedule orders for the selected dates.' }}</td>
      </tr>
      <tr></tr>
      <tr></tr>
      <tr>
          <th width="25">Date</th>
          <th width="20">Total</th>
          <th width="20">Qualified</th>
          <th width="25">Estimate &amp; Appt Schedule</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($dailySummary as $row)
        <tr>
            <td width="25" height="25" text-align="left" valign="middle">
              {{ $row['date'] }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $row['new_request_qualified'] }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $row['qualified'] }}
            </td>
            <td width="25" height="25" text-align="center" valign="middle">
              {{ $row['estimate_appt_schedule'] }}
            </td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td width="25" height="25" text-align="left" valign="middle"><strong>Totals</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totals['total'] }}</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totals['qualified'] }}</strong></td>
            <td width="25" height="25" text-align="center" valign="middle"><strong>{{ $totals['estimate_appt_schedule'] }}</strong></td>
        </tr>
    </tfoot>
</table>
