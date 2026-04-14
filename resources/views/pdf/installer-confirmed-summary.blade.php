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
    .unassigned {
        background-color: #f2f2f2;
    }
</style>

<table>
    <thead>
        <tr>
            <td class="header" colspan="5">
                Installer Confirmed Orders ({{ $startDate }} to {{ $endDate }})
            </td>
        </tr>
        <tr class="totals">
            <td colspan="2">Total Confirmed Orders: {{ $totalConfirmed }}</td>
            <td>Total Completed Orders: {{ $totalCompleted }}</td>
            <td colspan="2">Total Project Payment: {{ '$' . number_format($totalAssigned, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <th>Installer</th>
            <th>Company</th>
            <th>Confirmed Orders</th>
            <th>Completed Orders</th>
            <th>Total Project Payment</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($summary as $item)
            <tr class="{{ empty($item->installer_name) ? 'unassigned' : '' }}">
                <td>{{ $item->installer_name ?? 'PICKUP OR DELIVERY ONLY' }}</td>
                <td>{{ $item->company_name ?? '-' }}</td>
                <td>{{ $item->confirmed_orders }}</td>
                <td>{{ $item->completed_orders }}</td>
                <td>{{ '$' . number_format($item->assigned_amount ?? 0, 2, '.', ',') }}</td>
            </tr>
        @endforeach
        <tr class="totals">
            <td><strong>Totals</strong></td>
            <td></td>
            <td><strong>{{ $totalConfirmed }}</strong></td>
            <td><strong>{{ $totalCompleted }}</strong></td>
            <td><strong>{{ '$' . number_format($totalAssigned, 2, '.', ',') }}</strong></td>
        </tr>
    </tbody>
</table>
