<table>
    <thead>
        <tr>
            <td colspan="4" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
                Stock Materials Report
            </td>
            <td colspan="3" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
                Search: {{ ($filters['search'] ?? '') !== '' ? $filters['search'] : 'ALL' }}
            </td>
        </tr>
        <tr></tr>
        <tr></tr>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Area</th>
            <th>Cost</th>
            <th>Requested Date</th>
            <th>Quote ID</th>
            <th>Quote ID Received Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($materials as $material)
            <tr>
                <td>{{ $material['name'] ?? 'N/A' }}</td>
                <td>{{ $material['description'] ?? 'N/A' }}</td>
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
