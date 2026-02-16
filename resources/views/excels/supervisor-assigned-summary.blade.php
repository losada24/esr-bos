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
        <td style="font-weight: bold;">Total Execution &amp; Not Completed: {{ $totalExecutionNotCompleted }}</td>
        <td colspan="2" style="font-weight: bold;">Total Inspection &amp; Not Completed: {{ $totalInspectionNotCompleted }}</td>
      </tr>
      <tr></tr>
      <tr></tr>
      <tr>
          <th width="50">Supervisor</th>
          <th width="20">Confirmed Orders</th>
          <th width="20">Confirmed &amp; Completed</th>
          <th width="20">Execution &amp; Not Completed</th>
          <th width="20">Inspection &amp; Not Completed</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($summary as $item)
        <tr>
            <td width="50" height="25" text-align="left" valign="middle">
              {{ $item->supervisor_name ?? 'PICKUP OR DELIVERY ONLY' }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $item->confirmed_orders }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $item->confirmed_completed_orders }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $item->execution_not_completed_orders }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $item->inspection_not_completed_orders }}
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
    </tfoot>
</table>
