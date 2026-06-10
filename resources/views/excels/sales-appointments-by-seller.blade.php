<table>
  <thead>
    <tr>
      <td colspan="3" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
        Sales Assigned Orders & Assigned With Appointment Report ({{ $startDate }} to {{ $endDate }})
      </td>
    </tr>
    <tr>
      <td colspan="3" style="font-weight: bold;">
        Total Assigned Orders: {{ $totals['assigned_orders'] }} | Total Assigned With Appointment: {{ $totals['assigned_with_appointment'] }}
      </td>
    </tr>
    <tr></tr>
    <tr></tr>
    <tr>
      <th width="45">Salesperson</th>
      <th width="20">Assigned Orders</th>
      <th width="24">Assigned With Appointment</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($summary as $item)
      <tr>
        <td width="45" height="25" text-align="left" valign="middle">
          {{ $item->seller_name }}
        </td>
        <td width="20" height="25" text-align="center" valign="middle">
          {{ $item->assigned_orders_count }}
        </td>
        <td width="24" height="25" text-align="center" valign="middle">
          {{ $item->assigned_with_appointment_count }}
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="3">No assigned orders found for the selected dates.</td>
      </tr>
    @endforelse
  </tbody>
  <tfoot>
    <tr>
      <td width="45" height="25" text-align="left" valign="middle"><strong>Total</strong></td>
      <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totals['assigned_orders'] }}</strong></td>
      <td width="24" height="25" text-align="center" valign="middle"><strong>{{ $totals['assigned_with_appointment'] }}</strong></td>
    </tr>
  </tfoot>
</table>
