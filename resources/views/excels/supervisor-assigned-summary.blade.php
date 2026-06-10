<table>
    <thead>
      <tr>
        <td colspan="5" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
            Supervisor Assigned Orders ({{ $startDate }} to {{ $endDate }})
        </td>
      </tr>
      <tr>
        <td style="font-weight: bold;">Total Confirmed Orders: {{ $totalConfirmed }}</td>
        <td style="font-weight: bold;">Total Confirmed &amp; Completed: {{ $totalConfirmedCompleted }}</td>
        <td style="font-weight: bold;">Total Current Open Orders: {{ $totalExecutionNotCompleted }}</td>
        <td colspan="2" style="font-weight: bold;">Total Current Inspection Orders: {{ $totalInspectionNotCompleted }}</td>
      </tr>
      <tr></tr>
      <tr></tr>
      <tr>
          <th width="50">Supervisor</th>
          <th width="20">Confirmed Orders</th>
          <th width="20">Confirmed &amp; Completed</th>
          <th width="20">Current Open Orders*</th>
          <th width="20">Current Inspection Orders*</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($summary as $item)
        @php
          $isPickupOrDeliveryOnly = $item->supervisor_name === 'PICKUP OR DELIVERY ONLY';
        @endphp
        <tr>
            <td width="50" height="25" text-align="left" valign="middle">
              {{ $item->supervisor_name ?? 'UNASSIGNED' }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $item->confirmed_orders }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $item->confirmed_completed_orders }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $isPickupOrDeliveryOnly ? 0 : $item->execution_not_completed_orders }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $isPickupOrDeliveryOnly ? 0 : $item->inspection_not_completed_orders }}
            </td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td width="50" height="25" text-align="left" valign="middle"><strong>Totals</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totalConfirmed }}</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totalConfirmedCompleted }}</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totalExecutionNotCompleted }}</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totalInspectionNotCompleted }}</strong></td>
        </tr>
        <tr>
            <td colspan="5">* Current Open Orders and Current Inspection Orders use current order status and do not depend on the selected date range.</td>
        </tr>
    </tfoot>
</table>
