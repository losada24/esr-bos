<table>
    <thead>
      <tr>
        <td colspan="5" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
            Owner Report ({{ $startDate }} to {{ $endDate }})
        </td>
      </tr>
      <tr>
        <td colspan="2" style="font-weight: bold;">Total Assigned Clients: {{ $totalEstimateOrders }}</td>
        <td style="font-weight: bold;">Total Estimate Amount (Project Amount): {{ '$' . number_format($totalEstimateAmount, 2, '.', ',') }}</td>
        <td style="font-weight: bold;">Total Closed Won Orders: {{ $totalClosedWonOrders }}</td>
        <td style="font-weight: bold;">Total Closed Won Amount: {{ '$' . number_format($totalClosedWonAmount, 2, '.', ',') }}</td>
      </tr>
      <tr></tr>
      <tr></tr>
      <tr>
          <th width="45">Salesperson</th>
          <th width="20">Total Assigned Clients</th>
          <th width="30">Estimate Amount (Project Amount)</th>
          <th width="20">Closed Won Orders</th>
          <th width="30">Closed Won Amount</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($summary as $item)
        <tr>
            <td width="45" height="25" text-align="left" valign="middle">
              {{ $item->owner_name ?? 'UNASSIGNED OWNER' }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $item->estimate_orders }}
            </td>
            <td width="30" height="25" text-align="right" valign="middle">
              {{ '$' . number_format($item->estimate_amount ?? 0, 2, '.', ',') }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $item->closed_won_orders }}
            </td>
            <td width="30" height="25" text-align="right" valign="middle">
              {{ '$' . number_format($item->closed_won_amount ?? 0, 2, '.', ',') }}
            </td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td width="45" height="25" text-align="left" valign="middle"><strong>Totals</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totalEstimateOrders }}</strong></td>
            <td width="30" height="25" text-align="right" valign="middle">
              <strong>{{ '$' . number_format($totalEstimateAmount, 2, '.', ',') }}</strong>
            </td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totalClosedWonOrders }}</strong></td>
            <td width="30" height="25" text-align="right" valign="middle">
              <strong>{{ '$' . number_format($totalClosedWonAmount, 2, '.', ',') }}</strong>
            </td>
        </tr>
    </tfoot>
</table>
