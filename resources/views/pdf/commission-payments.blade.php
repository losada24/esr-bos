<style>
    table {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size: 10px;
    }
    th, td {
        border: 1px solid #000;
        padding: 5px;
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
            <td class="header" colspan="18">
                Commission Payments Report ({{ $startDate }} to {{ $endDate }})
            </td>
        </tr>
        <tr class="totals">
            <td colspan="18">Accounting Status: {{ $selectedStatus ?? 'ALL' }} | Commission Status: {{ $selectedCommissionStatus ?? 'ALL' }} | Beneficiary: {{ $beneficiarySearch !== '' ? $beneficiarySearch : 'ALL' }}</td>
        </tr>
        <tr class="totals">
            <td colspan="18">Review Payments: {{ count($reviewPayments) }} | Total Review Amount: {{ '$' . number_format((float) collect($reviewPayments)->sum('payment_amount'), 2, '.', ',') }}</td>
        </tr>
        <tr>
            <th>Accounting Status</th>
            <th>Order</th>
            <th>Commission</th>
            <th>#Invoice</th>
            <th>Beneficiary</th>
            <th>Project Payment Method</th>
            <th>Type Of Financing</th>
            <th>Project Amount</th>
            <th>Commission Fee</th>
            <th>Base</th>
            <th>Percentage</th>
            <th>Total Commission</th>
            <th>Pending</th>
            <th>Payment Type</th>
            <th>Payment</th>
            <th>Other Cost</th>
            <th>Total Payment</th>
            <th>Payment Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($reviewPayments as $payment)
            <tr>
                <td>{{ $payment['accounting_status'] }}</td>
                <td>{{ $payment['order_name'] }}</td>
                <td>#{{ $payment['commission_id'] }}</td>
                <td>{{ $payment['invoice_number'] ?: ($payment['order_number'] ? '#' . $payment['order_number'] : '-') }}</td>
                <td>{{ $payment['beneficiary_name'] }}</td>
                <td>{{ $payment['project_payment_method'] ?: '-' }}</td>
                <td>{{ $payment['type_of_financing'] ?: '-' }}</td>
                <td>{{ '$' . number_format((float) $payment['project_amount'], 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) $payment['commission_fee'], 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) $payment['commission_base'], 2, '.', ',') }}</td>
                <td>{{ $payment['commission_percentage'] !== null ? number_format((float) $payment['commission_percentage'], 2, '.', ',') . '%' : '-' }}</td>
                <td>{{ '$' . number_format((float) $payment['commission_total'], 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) $payment['commission_pending'], 2, '.', ',') }}</td>
                <td>{{ ($payment['payment_kind'] ?? 'REGULAR') === 'EXTRA_ADJUSTMENT' ? 'Extra Adjustment' : 'Regular' }}</td>
                <td>{{ '$' . number_format((float) $payment['payment_base_amount'], 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) $payment['payment_other_cost_amount'], 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) $payment['payment_amount'], 2, '.', ',') }}</td>
                <td>{{ $payment['payment_status'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="18">No review payments found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
