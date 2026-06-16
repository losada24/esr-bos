@php
    $isBm = ($filters['type'] ?? 'services') === 'bm';
    $colspan = $isBm ? 9 : 20;
@endphp

<table>
    <thead>
        <tr>
            <td colspan="{{ $isBm ? 3 : 5 }}" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
                {{ $isBm ? 'BM Report' : 'Services Report' }}
            </td>
            <td colspan="{{ $isBm ? 3 : 5 }}" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
                Search: {{ ($filters['search'] ?? '') !== '' ? $filters['search'] : 'ALL' }}
            </td>
            <td colspan="{{ $isBm ? 3 : 10 }}" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
                @if (! $isBm)
                    Status: {{ ($filters['status'] ?? '') !== '' ? $filters['status'] : 'ALL' }} | Priority: {{ ($filters['priority'] ?? '') !== '' ? $filters['priority'] : 'ALL' }}
                @else
                    Type: BM
                @endif
            </td>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr>
            @if ($isBm)
                <th>Order Name</th>
                <th>Supervisor</th>
                <th>QTY</th>
                <th>Request Date</th>
                <th>Picked Up By</th>
                <th>Invoice #</th>
                <th>Invoice Status</th>
                <th>Updated</th>
                <th>Service Name</th>
            @else
                <th>Service Name</th>
                <th>Client</th>
                <th>Order Address</th>
                <th>Service ID</th>
                <th>Created Date</th>
                <th>Service Type</th>
                <th>Description</th>
                <th>Requires Part</th>
                <th>Requested Parts</th>
                <th>Parts Available</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Supervisor</th>
                <th>Assignee</th>
                <th>Target Date</th>
                <th>Scheduled Date</th>
                <th>Executed Date</th>
                <th>Open Days</th>
                <th>Closure Result</th>
                <th>Observations</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse ($serviceControls as $serviceControl)
            <tr>
                @if ($isBm)
                    <td>{{ $serviceControl['order']['name'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['order']['supervisor']['name'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['bm_quantity'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['bm_requested_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['bm_picked_up_by'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['bm_invoice_number'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['bm_invoice_status'] ?? 'N/A' }}</td>
                    <td>{{ ! empty($serviceControl['updated_at']) ? \Carbon\Carbon::parse($serviceControl['updated_at'])->format('m/d/Y h:i A') : 'N/A' }}</td>
                    <td>{{ $serviceControl['service_name'] ?? 'N/A' }}</td>
                @else
                    <td>{{ $serviceControl['service_name'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['order']['client']['name'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['order']['address_label'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['service_id'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['service_created_date'] ?? 'N/A' }}</td>
                    <td>{{ is_array($serviceControl['service_type'] ?? null) ? implode(', ', $serviceControl['service_type']) : ($serviceControl['service_type'] ?? 'N/A') }}</td>
                    <td>{{ $serviceControl['description'] ?? 'N/A' }}</td>
                    <td>{{ ! empty($serviceControl['requires_part']) ? 'Yes' : 'No' }}</td>
                    <td>{{ ! empty($serviceControl['requested_parts']) ? 'Yes' : 'No' }}</td>
                    <td>{{ ! empty($serviceControl['parts_available']) ? 'Yes' : 'No' }}</td>
                    <td>{{ $serviceControl['service_status'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['priority'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['order']['supervisor']['name'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['assignee_name'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['target_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['scheduled_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['executed_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['open_days'] ?? 0 }}</td>
                    <td>{{ $serviceControl['closure_result'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['observations'] ?? 'N/A' }}</td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="{{ $colspan }}">No {{ $isBm ? 'BM records' : 'services' }} found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
