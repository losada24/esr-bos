<table>
  <thead>
    <tr>
      <td colspan="2" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
        Sales Appointments Report ({{ $startDate }} to {{ $endDate }})
      </td>
    </tr>
    <tr>
      <td colspan="2" style="font-weight: bold;">Total Appointments: {{ $totals['appointments'] }}</td>
    </tr>
    <tr></tr>
    <tr></tr>
    <tr>
      <th width="45">Salesperson</th>
      <th width="20">Appointments</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($summary as $item)
      <tr>
        <td width="45" height="25" text-align="left" valign="middle">
          {{ $item->seller_name }}
        </td>
        <td width="20" height="25" text-align="center" valign="middle">
          {{ $item->appointments_count }}
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="2">No appointments found for the selected dates.</td>
      </tr>
    @endforelse
  </tbody>
  <tfoot>
    <tr>
      <td width="45" height="25" text-align="left" valign="middle"><strong>Total</strong></td>
      <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totals['appointments'] }}</strong></td>
    </tr>
  </tfoot>
</table>
