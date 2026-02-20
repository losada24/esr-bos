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
      <td class="header" colspan="2">
        Sales Appointments Report ({{ $startDate }} to {{ $endDate }})
      </td>
    </tr>
    <tr class="totals">
      <td colspan="2">Total Appointments: {{ $totals['appointments'] }}</td>
    </tr>
    <tr>
      <th>Salesperson</th>
      <th>Appointments</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($summary as $item)
      <tr>
        <td>{{ $item->seller_name }}</td>
        <td>{{ $item->appointments_count }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="2">No appointments found for the selected dates.</td>
      </tr>
    @endforelse
    <tr class="totals">
      <td><strong>Total</strong></td>
      <td><strong>{{ $totals['appointments'] }}</strong></td>
    </tr>
  </tbody>
</table>
