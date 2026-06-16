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
    $colspan = $isBm ? 9 : 20;
@endphp

<div class="table-container">
<div class="table-wrapper">
<table>
    <thead>
        <tr>
            <td colspan="{{ $isBm ? 3 : 5 }}" class="header-info">
                {{ $isBm ? 'BM Report' : 'Services Report' }}
            </td>
            <td colspan="{{ $isBm ? 3 : 5 }}" class="header-info">
                Search: {{ ($filters['search'] ?? '') !== '' ? $filters['search'] : 'ALL' }}
            </td>
            <td colspan="{{ $isBm ? 3 : 10 }}" class="header-info">
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
                <th width="45">Service Name</th>
                <th width="30">Client</th>
                <th width="55">Order Address</th>
                <th width="25">Service ID</th>
                <th width="25">Created Date</th>
                <th width="25">Service Type</th>
                <th width="60">Description</th>
                <th width="20">Requires Part</th>
                <th width="20">Requested Parts</th>
                <th width="20">Parts Available</th>
                <th width="25">Status</th>
                <th width="20">Priority</th>
                <th width="30">Supervisor</th>
                <th width="30">Assignee</th>
                <th width="25">Target Date</th>
                <th width="25">Scheduled Date</th>
                <th width="25">Executed Date</th>
                <th width="20">Open Days</th>
                <th width="30">Closure Result</th>
                <th width="60">Observations</th>
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
                    <td class="left-cell">{{ $serviceControl['service_name'] ?? 'N/A' }}</td>
                    <td class="left-cell">{{ $serviceControl['order']['client']['name'] ?? 'N/A' }}</td>
                    <td class="left-cell compact">{{ $serviceControl['order']['address_label'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['service_id'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['service_created_date'] ?? 'N/A' }}</td>
                    <td>{{ is_array($serviceControl['service_type'] ?? null) ? implode(', ', $serviceControl['service_type']) : ($serviceControl['service_type'] ?? 'N/A') }}</td>
                    <td class="left-cell compact">{{ $serviceControl['description'] ?? 'N/A' }}</td>
                    <td>{{ ! empty($serviceControl['requires_part']) ? 'Yes' : 'No' }}</td>
                    <td>{{ ! empty($serviceControl['requested_parts']) ? 'Yes' : 'No' }}</td>
                    <td>{{ ! empty($serviceControl['parts_available']) ? 'Yes' : 'No' }}</td>
                    <td>{{ $serviceControl['service_status'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['priority'] ?? 'N/A' }}</td>
                    <td class="left-cell">{{ $serviceControl['order']['supervisor']['name'] ?? 'N/A' }}</td>
                    <td class="left-cell">{{ $serviceControl['assignee_name'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['target_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['scheduled_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['executed_date'] ?? 'N/A' }}</td>
                    <td>{{ $serviceControl['open_days'] ?? 0 }}</td>
                    <td>{{ $serviceControl['closure_result'] ?? 'N/A' }}</td>
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
