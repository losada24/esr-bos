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
                Supervisor Assigned Orders ({{ $startDate }} to {{ $endDate }})
            </td>
        </tr>
        <tr class="totals">
            <td>Total Confirmed Orders: {{ $totalConfirmed }}</td>
            <td>Total Confirmed &amp; Completed: {{ $totalConfirmedCompleted }}</td>
            <td>Total Current Open Orders: {{ $totalExecutionNotCompleted }}</td>
            <td colspan="2">Total Current Inspection Orders: {{ $totalInspectionNotCompleted }}</td>
        </tr>
        <tr>
            <th>Supervisor</th>
            <th>Confirmed Orders</th>
            <th>Confirmed &amp; Completed</th>
            <th>Current Open Orders*</th>
            <th>Current Inspection Orders*</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($summary as $item)
            @php
                $isPickupOrDeliveryOnly = $item->supervisor_name === 'PICKUP OR DELIVERY ONLY';
            @endphp
            <tr class="{{ $isPickupOrDeliveryOnly ? 'unassigned' : '' }}">
                <td>{{ $item->supervisor_name ?? 'UNASSIGNED' }}</td>
                <td>{{ $item->confirmed_orders }}</td>
                <td>{{ $item->confirmed_completed_orders }}</td>
                <td>{{ $isPickupOrDeliveryOnly ? 0 : $item->execution_not_completed_orders }}</td>
                <td>{{ $isPickupOrDeliveryOnly ? 0 : $item->inspection_not_completed_orders }}</td>
            </tr>
        @endforeach
        <tr class="totals">
            <td><strong>Totals</strong></td>
            <td><strong>{{ $totalConfirmed }}</strong></td>
            <td><strong>{{ $totalConfirmedCompleted }}</strong></td>
            <td><strong>{{ $totalExecutionNotCompleted }}</strong></td>
            <td><strong>{{ $totalInspectionNotCompleted }}</strong></td>
        </tr>
        <tr>
            <td colspan="5" style="font-size: 11px; text-align: left;">* Current Open Orders and Current Inspection Orders use current order status and do not depend on the selected date range.</td>
        </tr>
    </tbody>
</table>
