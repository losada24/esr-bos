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
            <td class="header" colspan="5">
                Accounting Status Summary ({{ $startDate }} to {{ $endDate }})
            </td>
        </tr>
        <tr class="totals">
            <td colspan="5">Status Filter: {{ $selectedStatus ?? 'ALL' }}</td>
        </tr>
        <tr class="totals">
            <td colspan="5">Total Rows: {{ $totals['total'] }}</td>
        </tr>
        <tr class="totals">
            <td colspan="5">ACCOUNT RECEIPT: {{ $totals['account_receipt'] }}</td>
        </tr>
        <tr class="totals">
            <td colspan="5">COMPLETE: {{ $totals['complete'] }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <th>Order Name</th>
            <th>Owner</th>
            <th>Amount</th>
            <th>Status Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['status'] }}</td>
                <td>{{ $row['order_name'] ?? '-' }}</td>
                <td>{{ $row['owner'] ?: '-' }}</td>
                <td>{{ '$' . number_format((float) ($row['amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ $row['status_date'] ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No data for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
