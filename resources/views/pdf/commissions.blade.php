<style>
    table {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size: 11px;
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
            <td class="header" colspan="11">
                Commissions Report ({{ $startDate }} to {{ $endDate }})
            </td>
        </tr>
        <tr class="totals">
            <td colspan="11">Accounting Status: {{ $selectedStatus ?? 'ALL' }} | Commission Status: {{ $selectedCommissionStatus ?? 'ALL' }} | Beneficiary: {{ $beneficiarySearch !== '' ? $beneficiarySearch : 'ALL' }}</td>
        </tr>
        <tr class="totals">
            <td colspan="11">Orders: {{ $totals['orders'] }} | Commissions: {{ $totals['commissions'] }} | Total: {{ '$' . number_format((float) $totals['total_commission'], 2, '.', ',') }} | Paid: {{ '$' . number_format((float) $totals['total_paid'], 2, '.', ',') }} | Pending: {{ '$' . number_format((float) $totals['total_pending'], 2, '.', ',') }}</td>
        </tr>
        <tr>
            <th>Accounting Status</th>
            <th>Order Status</th>
            <th>Order</th>
            <th>Owners</th>
            <th>Beneficiary</th>
            <th>Relation</th>
            <th>Commission Status</th>
            <th>Next Payment</th>
            <th>Paid</th>
            <th>Pending</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['accounting_status'] }}</td>
                <td>{{ $row['order_status'] }}</td>
                <td>{{ $row['order_name'] }}</td>
                <td>{{ $row['owner_names'] ?: '-' }}</td>
                <td>{{ $row['beneficiary_name'] ?? 'No commission yet' }}</td>
                <td>{{ $row['beneficiary_relation'] ?? '-' }}</td>
                <td>{{ $row['commission_status'] ?? '-' }}</td>
                <td>
                    @if ($row['next_payment_status'])
                        {{ $row['next_payment_status'] }} · {{ '$' . number_format((float) $row['next_payment_amount'], 2, '.', ',') }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ '$' . number_format((float) $row['paid_amount'], 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) $row['pending_amount'], 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) $row['commission_total'], 2, '.', ',') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="11">No commissions found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
