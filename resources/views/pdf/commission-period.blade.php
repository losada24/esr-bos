<style>
    @page {
        margin: 10px 12px;
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 10.5px;
        margin: 0;
        padding: 0;
        line-height: 1.28;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    thead {
        display: table-header-group;
    }

    tfoot {
        display: table-footer-group;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    th, td {
        border: 1px solid #000;
        padding: 5px 6px;
        text-align: left;
        vertical-align: top;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    th {
        background-color: #f0f0f0;
        font-weight: bold;
    }

    .header {
        font-weight: bold;
        font-size: 13px;
        background-color: #e0e0e0;
        padding: 7px;
        text-align: left;
    }

    .meta {
        font-weight: bold;
        background-color: #f7f7f7;
        font-size: 10px;
    }
</style>

@php
    $summary = $period['snapshot']['summary'] ?? [];
    $payments = $period['snapshot']['payments'] ?? [];
    $selectedBeneficiary = $period['selected_beneficiary'] ?? null;
    $totalLabel = ($period['status'] ?? '') === 'OPEN' ? 'Total In Period' : 'Total Paid';
@endphp

<table>
    <thead>
        <tr>
            <td class="header" colspan="18">
                Commission Period {{ $period['label'] }} ({{ $period['start_date'] }} to {{ $period['end_date'] }})
            </td>
        </tr>
        <tr class="meta">
            <td colspan="18">
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
            <tr class="meta">
                <td colspan="18">
                    Beneficiary Filter: {{ $selectedBeneficiary['beneficiary_name'] ?? '-' }}
                    ({{ $selectedBeneficiary['beneficiary_relation'] ?? '-' }})
                </td>
            </tr>
        @endif
        <tr>
            <th>Accounting Status</th>
            <th>Order</th>
            <th>#Invoice</th>
            <th>Beneficiary</th>
            <th>Project Payment Method</th>
            <th>Project Amount</th>
            <th>Commission Fee</th>
            <th>Base</th>
            <th>Percentage</th>
            <th>Total Commission</th>
            <th>Pending</th>
            <th>Payment Type</th>
            <th>Paid Accumulated</th>
            <th>Payment To Pay</th>
            <th>Other Cost</th>
            <th>Total Payment</th>
            <th>Payment Status</th>
            <th>Paid At</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($payments as $payment)
            <tr>
                <td>
                    {{ $payment['accounting_status'] ?? '-' }}
                </td>
                <td>
                    <div>{{ $payment['order']['name'] ?? '-' }}</div>
                    <div>{{ ($payment['order']['status'] ?? '-') . ' · ' . (!empty($payment['order']['owners']) ? implode(', ', $payment['order']['owners']) : '-') }}</div>
                </td>
                <td>{{ $payment['order']['invoice_number'] ?? (($payment['order']['order_number'] ?? null) ? '#' . $payment['order']['order_number'] : '-') }}</td>
                <td>
                    <div>{{ $payment['commission']['beneficiary_name'] ?? '-' }}</div>
                    <div>{{ $payment['commission']['beneficiary_relation'] ?? '-' }}</div>
                </td>
                <td>{{ $payment['order']['project_payment_method'] ?? '-' }}</td>
                <td>{{ '$' . number_format((float) ($payment['order']['project_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['commission']['fee_amount_snapshot'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['commission']['base_amount_snapshot'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ isset($payment['commission']['percentage_value']) ? number_format((float) $payment['commission']['percentage_value'], 2, '.', ',') . '%' : '-' }}</td>
                <td>{{ '$' . number_format((float) ($payment['commission']['commission_total_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['commission']['commission_pending_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ ($payment['payment_kind'] ?? 'REGULAR') === 'EXTRA_ADJUSTMENT' ? 'Extra Adjustment' : 'Regular' }}</td>
                <td>{{ '$' . number_format((float) ($payment['commission']['commission_paid_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['payment_base_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['payment_other_cost_amount'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ '$' . number_format((float) ($payment['payment_total_to_pay'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ $payment['status'] ?? '-' }}</td>
                <td>{{ $payment['paid_at'] ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="18">No payments snapshot available for this period.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="meta">
            <td colspan="5" style="text-align: right;">Total</td>
            <td>{{ '$' . number_format((float) collect($payments)->sum(fn ($payment) => (float) ($payment['order']['project_amount'] ?? 0)), 2, '.', ',') }}</td>
            <td>{{ '$' . number_format((float) collect($payments)->sum(fn ($payment) => (float) ($payment['commission']['fee_amount_snapshot'] ?? 0)), 2, '.', ',') }}</td>
            <td>{{ '$' . number_format((float) collect($payments)->sum(fn ($payment) => (float) ($payment['commission']['base_amount_snapshot'] ?? 0)), 2, '.', ',') }}</td>
            <td></td>
            <td>{{ '$' . number_format((float) collect($payments)->sum(fn ($payment) => (float) ($payment['commission']['commission_total_amount'] ?? 0)), 2, '.', ',') }}</td>
            <td>{{ '$' . number_format((float) collect($payments)->sum(fn ($payment) => (float) ($payment['commission']['commission_pending_amount'] ?? 0)), 2, '.', ',') }}</td>
            <td></td>
            <td>{{ '$' . number_format((float) collect($payments)->sum(fn ($payment) => (float) ($payment['commission']['commission_paid_amount'] ?? 0)), 2, '.', ',') }}</td>
            <td>{{ '$' . number_format((float) collect($payments)->sum(fn ($payment) => (float) ($payment['payment_base_amount'] ?? 0)), 2, '.', ',') }}</td>
            <td>{{ '$' . number_format((float) collect($payments)->sum(fn ($payment) => (float) ($payment['payment_other_cost_amount'] ?? 0)), 2, '.', ',') }}</td>
            <td>{{ '$' . number_format((float) collect($payments)->sum(fn ($payment) => (float) ($payment['payment_total_to_pay'] ?? 0)), 2, '.', ',') }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>
