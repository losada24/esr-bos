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
            <td class="header" colspan="4">
                Daily Order Status ({{ $startDate }} to {{ $endDate }})
            </td>
        </tr>
        <tr class="totals">
            <td colspan="4">Total: {{ $totals['total'] }}</td>
        </tr>
        <tr class="totals">
            <td colspan="4">Total Orders: {{ $totals['total_orders'] ?? count($orderLists['total'] ?? []) }}</td>
        </tr>
        <tr class="totals">
            <td colspan="4">Total Qualified: {{ $totals['qualified'] }}</td>
        </tr>
        <tr class="totals">
            <td colspan="4">Total Estimate &amp; Appt Schedule: {{ $totals['estimate_appt_schedule'] }}</td>
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
        <tr>
            <th>Date</th>
            <th>Total</th>
            <th>Qualified</th>
            <th>Estimate &amp; Appt Schedule</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($dailySummary as $row)
            <tr>
                <td>{{ $row['date'] }}</td>
                <td>{{ $row['new_request_qualified'] }}</td>
                <td>{{ $row['qualified'] }}</td>
                <td>{{ $row['estimate_appt_schedule'] }}</td>
            </tr>
        @endforeach
        <tr class="totals">
            <td><strong>Totals</strong></td>
            <td><strong>{{ $totals['total'] }}</strong></td>
            <td><strong>{{ $totals['qualified'] }}</strong></td>
            <td><strong>{{ $totals['estimate_appt_schedule'] }}</strong></td>
        </tr>
    </tbody>
</table>
