<style>
    @page {
        margin: 12px;
    }

    body {
        margin: 0;
        padding: 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size: 12px;
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
        font-size: 16px;
        text-align: left;
        background-color: #f0f0f0;
        padding: 8px;
    }

    .left-cell {
        text-align: left;
    }
</style>

<table>
    <thead>
        <tr>
            <td colspan="4" class="header-info">Stock Materials Report</td>
            <td colspan="3" class="header-info">
                Search: {{ ($filters['search'] ?? '') !== '' ? $filters['search'] : 'ALL' }}
            </td>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr>
            <th width="25">Name</th>
            <th width="45">Description</th>
            <th width="18">Area</th>
            <th width="15">Cost</th>
            <th width="22">Requested Date</th>
            <th width="20">Quote ID</th>
            <th width="25">Quote ID Received Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($materials as $material)
            <tr>
                <td class="left-cell">{{ $material['name'] ?? 'N/A' }}</td>
                <td class="left-cell">{{ $material['description'] ?? 'N/A' }}</td>
                <td>{{ $material['area'] ?? 'N/A' }}</td>
                <td>{{ $material['cost'] ?? 'N/A' }}</td>
                <td>{{ $material['requested_date'] ?? 'N/A' }}</td>
                <td>{{ $material['quote_id'] ?? 'N/A' }}</td>
                <td>{{ $material['quote_id_received_date'] ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No stock materials found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
