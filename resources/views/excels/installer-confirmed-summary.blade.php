<table>
    <thead>
      <tr>
        <td colspan="5" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
            Installer Confirmed Orders ({{ $startDate }} to {{ $endDate }})
        </td>
      </tr>
      <tr>
        <td colspan="2" style="font-weight: bold;">Total Confirmed Orders: {{ $totalConfirmed }}</td>
        <td style="font-weight: bold;">Total Completed Orders: {{ $totalCompleted }}</td>
        <td colspan="2" style="font-weight: bold;">Total Project Payment: {{ '$' . number_format($totalAssigned, 2, '.', ',') }}</td>
      </tr>
      <tr></tr>
      <tr></tr>
      <tr>
          <th width="50">Installer</th>
          <th width="50">Company</th>
          <th width="20">Confirmed Orders</th>
          <th width="20">Completed Orders</th>
          <th width="30">Total Project Payment</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($summary as $item)
        <tr>
            <td width="50" height="25" text-align="left" valign="middle">
              {{ $item->installer_name ?? 'UNASSIGNED' }}
            </td>
            <td width="50" height="25" text-align="left" valign="middle">
              {{ $item->company_name ?? '-' }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $item->confirmed_orders }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $item->completed_orders }}
            </td>
            <td width="30" height="25" text-align="right" valign="middle">
              {{ '$' . number_format($item->assigned_amount ?? 0, 2, '.', ',') }}
            </td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td width="50" height="25" text-align="left" valign="middle"><strong>Totals</strong></td>
            <td width="50" height="25" text-align="left" valign="middle"></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totalConfirmed }}</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totalCompleted }}</strong></td>
            <td width="30" height="25" text-align="right" valign="middle">
              <strong>{{ '$' . number_format($totalAssigned, 2, '.', ',') }}</strong>
            </td>
        </tr>
    </tfoot>
</table>
