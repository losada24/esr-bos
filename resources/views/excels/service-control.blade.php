@php
    $isBm = ($filters['type'] ?? 'services') === 'bm';
    $isQuotes = ($filters['type'] ?? 'services') === 'quotes';
    $colspan = $isBm ? 9 : 17;
    $reportTitle = $isBm ? 'BM Report' : ($isQuotes ? 'Quotes Report' : 'Services Report');
    $serviceOrderNumber = function (array $serviceControl): string {
        $source = strtoupper((string) ($serviceControl['service_source'] ?? 'ESR'));

        if ($source === 'ESR') {
            return filled($serviceControl['external_order_id'] ?? null) ? (string) $serviceControl['external_order_id'] : 'N/A';
        }

        return $serviceControl['order']['parent_order']['order_number']
            ?? $serviceControl['order']['order_number']
            ?? 'N/A';
    };
    $ownerNames = function (array $serviceControl): string {
        $owners = $serviceControl['order']['owners'] ?? [];

        if (is_array($owners) && count($owners) > 0) {
            return collect($owners)->pluck('name')->filter()->implode(', ') ?: 'N/A';
        }

        return $serviceControl['order']['seller']['name'] ?? 'N/A';
    };
    $serviceTypes = function (array $serviceControl): string {
        $types = $serviceControl['service_type'] ?? null;

        return is_array($types) ? (implode(', ', $types) ?: 'N/A') : ($types ?: 'N/A');
    };
@endphp

<table>
    <thead>
        <tr>
            <td colspan="{{ $isBm ? 3 : 6 }}" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
                {{ $reportTitle }}
            </td>
            <td colspan="{{ $isBm ? 3 : 6 }}" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
                Search: {{ ($filters['search'] ?? '') !== '' ? $filters['search'] : 'ALL' }}
            </td>
            <td colspan="{{ $isBm ? 3 : 5 }}" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
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
                <th>Order #</th>
                <th>Service/Quote #</th>
                <th>Origin</th>
                <th>Service Name</th>
                <th>Company</th>
                <th>Client</th>
                <th>Owner</th>
                <th>Service Type</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Reception Date</th>
                <th>Put Into Production Date</th>
                <th>ETA</th>
                <th>Production Output Date</th>
                <th>Urgency Status</th>
                <th>Description</th>
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
                    <td>{{ $serviceOrderNumber($serviceControl) }}</td>
                    <td>{{ $serviceControl['service_id'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['service_source'] ?? 'ESR' }}</td>
                    <td>{{ $serviceControl['service_name'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['order']['company']['name'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['order']['client']['name'] ?? $serviceControl['client']['name'] ?? 'No client' }}</td>
                    <td>{{ $ownerNames($serviceControl) }}</td>
                    <td>{{ $serviceTypes($serviceControl) }}</td>
                    <td>{{ $serviceControl['service_status'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['priority'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['service_created_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['service_id_requested_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['eta_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['parts_received_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['urgency_status'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['description'] ?? 'N/A' }}</td>
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
