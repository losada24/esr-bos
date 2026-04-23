@php
    $summary = $period['snapshot']['summary'] ?? [];
    $payments = $period['snapshot']['payments'] ?? [];
    $selectedBeneficiary = $period['selected_beneficiary'] ?? null;
    $totalLabel = ($period['status'] ?? '') === 'OPEN' ? 'Total In Period' : 'Total Paid';
@endphp

<table>
    <thead>
        <tr>
            <td colspan="19" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
                Commission Period {{ $period['label'] }} ({{ $period['start_date'] }} to {{ $period['end_date'] }})
            </td>
        </tr>
        <tr>
            <td colspan="19" style="font-weight: bold;">
                Status: {{ $period['status'] }} |
                Closed At: {{ $period['closed_at'] ?? '-' }} |
                Payments: {{ $summary['payments_count'] ?? 0 }} |
                Orders: {{ $summary['orders_count'] ?? 0 }} |
                Commissions: {{ $summary['commissions_count'] ?? 0 }} |
                Beneficiaries: {{ $summary['beneficiaries_count'] ?? 0 }} |
                {{ $totalLabel }}: {{ '$' . number_format((float) ($summary['total_paid'] ?? 0), 2, '.', ',') }}
            </td>
        </tr>
        @if ($selectedBeneficiary)
            <tr>
                <td colspan="19" style="font-weight: bold;">
                    Beneficiary Filter: {{ $selectedBeneficiary['beneficiary_name'] ?? '-' }}
                    ({{ $selectedBeneficiary['beneficiary_relation'] ?? '-' }})
                </td>
            </tr>
        @endif
        <tr></tr>
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
            <th>Paid At</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($payments as $payment)
            <tr>
                <td>{{ $payment['accounting_status'] ?? '-' }}</td>
                <td>{{ $payment['order']['name'] ?? '-' }}</td>
                <td>#{{ $payment['commission']['id'] ?? '-' }}</td>
                <td>{{ $payment['order']['invoice_number'] ?? (($payment['order']['order_number'] ?? null) ? '#' . $payment['order']['order_number'] : '-') }}</td>
                <td>{{ $payment['commission']['beneficiary_name'] ?? '-' }}</td>
                <td>{{ $payment['order']['project_payment_method'] ?? '-' }}</td>
                <td>{{ $payment['order']['type_of_financing'] ?? '-' }}</td>
                <td>{{ '$' . number_format((float) ($payment['order']['project_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['commission']['fee_amount_snapshot'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['commission']['base_amount_snapshot'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ isset($payment['commission']['percentage_value']) ? number_format((float) $payment['commission']['percentage_value'], 2, '.', ',') . '%' : '-' }}</td>
                <td>{{ '$' . number_format((float) ($payment['commission']['commission_total_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['commission']['commission_pending_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ ($payment['payment_kind'] ?? 'REGULAR') === 'EXTRA_ADJUSTMENT' ? 'Extra Adjustment' : 'Regular' }}</td>
                <td>{{ '$' . number_format((float) ($payment['payment_base_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['payment_other_cost_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['payment_total_to_pay'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ $payment['status'] ?? '-' }}</td>
                <td>{{ $payment['paid_at'] ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="19">No payments snapshot available for this period.</td>
            </tr>
        @endforelse
    </tbody>
</table>
