<style>
    @page {
        margin: 4px 6px;
    }

    body {
        margin: 0;
        padding: 0;
    }

    .table-container {
        display: flex;
        justify-content: center;
        padding: 0;
    }

    .table-wrapper {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 400px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size: 15px;
        table-layout: fixed;
    }

    th, td {
        border: 1px solid #000;
        padding: 6px;
        text-align: center;
        vertical-align: middle;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    th {
        background-color: #f0f0f0;
        font-weight: bold;
    }

    tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .header-info {
        font-weight: bold;
        font-size: 18px;
        text-align: left;
        background-color: #f0f0f0;
        padding: 10px;
    }

    .left-cell {
        text-align: left;
    }

    .compact {
        font-size: 13px;
        line-height: 1.2;
    }
</style>

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

<div class="table-container">
<div class="table-wrapper">
<table>
    <thead>
        <tr>
            <td colspan="{{ $isBm ? 3 : 6 }}" class="header-info">
                {{ $reportTitle }}
            </td>
            <td colspan="{{ $isBm ? 3 : 6 }}" class="header-info">
                Search: {{ ($filters['search'] ?? '') !== '' ? $filters['search'] : 'ALL' }}
            </td>
            <td colspan="{{ $isBm ? 3 : 5 }}" class="header-info">
                @if ($isBm)
                    Type: BM
                @else
                    Status: {{ ($filters['status'] ?? '') !== '' ? $filters['status'] : 'ALL' }} | Priority: {{ ($filters['priority'] ?? '') !== '' ? $filters['priority'] : 'ALL' }}
                @endif
            </td>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr>
            @if ($isBm)
                <th width="50">Order Name</th>
                <th width="30">Supervisor</th>
                <th width="15">QTY</th>
                <th width="25">Request Date</th>
                <th width="35">Picked Up By</th>
                <th width="25">Invoice #</th>
                <th width="25">Invoice Status</th>
                <th width="30">Updated</th>
                <th width="50">Service Name</th>
            @else
                <th width="25">Order #</th>
                <th width="30">Service/Quote #</th>
                <th width="18">Origin</th>
                <th width="45">Service Name</th>
                <th width="35">Company</th>
                <th width="35">Client</th>
                <th width="35">Owner</th>
                <th width="30">Service Type</th>
                <th width="25">Status</th>
                <th width="20">Priority</th>
                <th width="30">Reception Date</th>
                <th width="35">Put Into Production Date</th>
                <th width="25">ETA</th>
                <th width="30">Production Output Date</th>
                <th width="40">Urgency Status</th>
                <th width="55">Description</th>
                <th width="55">Observations</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse ($serviceControls as $serviceControl)
            <tr>
                @if ($isBm)
                    <td class="left-cell">{{ $serviceControl['order']['name'] ?? 'N/A' }}</td>
                    <td class="left-cell">{{ $serviceControl['order']['supervisor']['name'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['bm_quantity'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['bm_requested_date'] ?? 'N/A' }}</td>
                    <td class="left-cell">{{ $serviceControl['bm_picked_up_by'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['bm_invoice_number'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['bm_invoice_status'] ?? 'N/A' }}</td>
                    <td>{{ ! empty($serviceControl['updated_at']) ? \Carbon\Carbon::parse($serviceControl['updated_at'])->format('m/d/Y h:i A') : 'N/A' }}</td>
                    <td class="left-cell">{{ $serviceControl['service_name'] ?? 'N/A' }}</td>
                @else
                    <td>{{ $serviceOrderNumber($serviceControl) }}</td>
                    <td>{{ $serviceControl['service_id'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['service_source'] ?? 'ESR' }}</td>
                    <td class="left-cell">{{ $serviceControl['service_name'] ?? 'N/A' }}</td>
                    <td class="left-cell">{{ $serviceControl['order']['company']['name'] ?? 'N/A' }}</td>
                    <td class="left-cell">{{ $serviceControl['order']['client']['name'] ?? $serviceControl['client']['name'] ?? 'No client' }}</td>
                    <td class="left-cell">{{ $ownerNames($serviceControl) }}</td>
                    <td>{{ $serviceTypes($serviceControl) }}</td>
                    <td>{{ $serviceControl['service_status'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['priority'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['service_created_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['service_id_requested_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['eta_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['parts_received_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['urgency_status'] ?? 'N/A' }}</td>
                    <td class="left-cell compact">{{ $serviceControl['description'] ?? 'N/A' }}</td>
                    <td class="left-cell compact">{{ $serviceControl['observations'] ?? 'N/A' }}</td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="{{ $colspan }}">No {{ $isBm ? 'BM records' : 'services' }} found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
