<style>
  table {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
    font-size: 12px;
  }
  th, td {
    border: 1px solid #000;
    padding: 6px;
    text-align: left;
    vertical-align: middle;
  }
  th {
    background-color: #f0f0f0;
    font-weight: bold;
  }
  .header {
    font-weight: bold;
    font-size: 14px;
    background-color: #e0e0e0;
    padding: 8px;
    text-align: left;
  }
  .totals {
    font-weight: bold;
    background-color: #f7f7f7;
  }
</style>

<table>
  <thead>
    <tr>
      <td class="header" colspan="3">
        Sales Assigned Orders & Assigned With Appointment Report ({{ $startDate }} to {{ $endDate }})
      </td>
    </tr>
    <tr class="totals">
      <td colspan="3">
        Total Assigned Orders: {{ $totals['assigned_orders'] }} | Total Assigned With Appointment: {{ $totals['assigned_with_appointment'] }}
      </td>
    </tr>
    <tr>
      <th>Salesperson</th>
      <th>Assigned Orders</th>
      <th>Assigned With Appointment</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($summary as $item)
      <tr>
        <td>{{ $item->seller_name }}</td>
        <td>{{ $item->assigned_orders_count }}</td>
        <td>{{ $item->assigned_with_appointment_count }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="3">No assigned orders found for the selected dates.</td>
      </tr>
    @endforelse
    <tr class="totals">
      <td><strong>Total</strong></td>
      <td><strong>{{ $totals['assigned_orders'] }}</strong></td>
      <td><strong>{{ $totals['assigned_with_appointment'] }}</strong></td>
    </tr>
  </tbody>
</table>
