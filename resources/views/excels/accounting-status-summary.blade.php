<table>
    <thead>
      <tr>
        <td colspan="5" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
            Accounting Status Summary ({{ $startDate }} to {{ $endDate }})
        </td>
      </tr>
      <tr>
        <td colspan="5" style="font-weight: bold;">
          Status Filter: {{ $selectedStatus ?? 'ALL' }}
        </td>
      </tr>
      <tr>
        <td colspan="5" style="font-weight: bold;">Total Rows: {{ $totals['total'] }}</td>
      </tr>
      <tr>
        <td colspan="5" style="font-weight: bold;">ACCOUNT RECEIPT: {{ $totals['account_receipt'] }}</td>
      </tr>
      <tr>
        <td colspan="5" style="font-weight: bold;">COMPLETE: {{ $totals['complete'] }}</td>
      </tr>
      <tr></tr>
      <tr></tr>
      <tr>
          <th width="25">Status</th>
          <th width="35">Order Name</th>
          <th width="25">Owner</th>
          <th width="20">Amount</th>
          <th width="25">Status Date</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($rows as $row)
        <tr>
            <td width="25" height="25" text-align="left" valign="middle">
              {{ $row['status'] }}
            </td>
            <td width="35" height="25" text-align="left" valign="middle">
              {{ $row['order_name'] ?? '-' }}
            </td>
            <td width="25" height="25" text-align="left" valign="middle">
              {{ $row['owner'] ?: '-' }}
            </td>
            <td width="20" height="25" text-align="right" valign="middle">
              {{ '$' . number_format((float) ($row['amount'] ?? 0), 2, '.', ',') }}
            </td>
            <td width="25" height="25" text-align="left" valign="middle">
              {{ $row['status_date'] ?? '-' }}
            </td>
        </tr>
      @empty
        <tr>
          <td colspan="5">No data for the selected filters.</td>
        </tr>
      @endforelse
    </tbody>
</table>
