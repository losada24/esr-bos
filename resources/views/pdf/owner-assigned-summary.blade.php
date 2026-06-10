@php
    $formatPercentage = static fn ($value) => number_format((float) $value, 2, '.', ',') . '%';
@endphp

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
            <td class="header" colspan="8">
                Owner Report ({{ $startDate }} to {{ $endDate }})
            </td>
        </tr>
        <tr class="totals">
            <td colspan="2">Total Assigned Clients: {{ $totalEstimateOrders }}</td>
            <td>Total Estimated Clients: {{ $totalEstimatedClients }}</td>
            <td>Total Estimate Amount (Project Amount): {{ '$' . number_format($totalEstimateAmount, 2, '.', ',') }}</td>
            <td>Total Closed Won Orders: {{ $totalClosedWonOrders }}</td>
            <td>% Closed Won Orders: {{ $formatPercentage($totalClosedWonOrdersPercentage) }}</td>
            <td>Total Closed Won Amount: {{ '$' . number_format($totalClosedWonAmount, 2, '.', ',') }}</td>
            <td>% Closed Won Amount: {{ $formatPercentage($totalClosedWonAmountPercentage) }}</td>
        </tr>
        <tr>
            <th>Salesperson</th>
            <th>Total Assigned Clients</th>
            <th>Estimated Clients</th>
            <th>Estimate Amount (Project Amount)</th>
            <th>Closed Won Orders</th>
            <th>% Closed Won Orders</th>
            <th>Closed Won Amount</th>
            <th>% Closed Won Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($summary as $item)
            <tr>
                <td>{{ $item->owner_name ?? 'UNASSIGNED OWNER' }}</td>
                <td>{{ $item->estimate_orders }}</td>
                <td>{{ $item->estimated_clients }}</td>
                <td>{{ '$' . number_format($item->estimate_amount ?? 0, 2, '.', ',') }}</td>
                <td>{{ $item->closed_won_orders }}</td>
                <td>{{ $formatPercentage($item->closed_won_orders_percentage ?? 0) }}</td>
                <td>{{ '$' . number_format($item->closed_won_amount ?? 0, 2, '.', ',') }}</td>
                <td>{{ $formatPercentage($item->closed_won_amount_percentage ?? 0) }}</td>
            </tr>
        @endforeach
        <tr class="totals">
            <td><strong>Totals</strong></td>
            <td><strong>{{ $totalEstimateOrders }}</strong></td>
            <td><strong>{{ $totalEstimatedClients }}</strong></td>
            <td><strong>{{ '$' . number_format($totalEstimateAmount, 2, '.', ',') }}</strong></td>
            <td><strong>{{ $totalClosedWonOrders }}</strong></td>
            <td><strong>{{ $formatPercentage($totalClosedWonOrdersPercentage) }}</strong></td>
            <td><strong>{{ '$' . number_format($totalClosedWonAmount, 2, '.', ',') }}</strong></td>
            <td><strong>{{ $formatPercentage($totalClosedWonAmountPercentage) }}</strong></td>
        </tr>
    </tbody>
</table>
