<table>
    <thead>
      <tr>
        <td colspan="5" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
            Daily Order Status ({{ $startDate }} to {{ $endDate }})
        </td>
      </tr>
      <tr>
        <td colspan="5" style="font-weight: bold;">Total: {{ $totals['total'] }}</td>
      </tr>
      <tr>
        <td colspan="5" style="font-weight: bold;">Total Orders: {{ $totals['total_orders'] ?? count($orderLists['total'] ?? []) }}</td>
      </tr>
      <tr>
        <td colspan="5" style="font-weight: bold;">Total Qualified: {{ $totals['qualified'] }}</td>
      </tr>
      <tr>
        <td colspan="5" style="font-weight: bold;">Total Estimate &amp; Appt Schedule: {{ $totals['estimate_appt_schedule'] }}</td>
      </tr>
      <tr>
        <td colspan="5" style="font-weight: bold;">Total Lost Request: {{ $totals['lost_request'] }}</td>
      </tr>
      <tr></tr>
      <tr></tr>
      <tr>
          <th width="25">Date</th>
          <th width="20">Total</th>
          <th width="20">Qualified</th>
          <th width="25">Estimate &amp; Appt Schedule</th>
          <th width="20">Lost Request</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($dailySummary as $row)
        <tr>
            <td width="25" height="25" text-align="left" valign="middle">
              {{ $row['date'] }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $row['new_request_qualified'] }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $row['qualified'] }}
            </td>
            <td width="25" height="25" text-align="center" valign="middle">
              {{ $row['estimate_appt_schedule'] }}
            </td>
            <td width="20" height="25" text-align="center" valign="middle">
              {{ $row['lost_request'] }}
            </td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td width="25" height="25" text-align="left" valign="middle"><strong>Totals</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totals['total'] }}</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totals['qualified'] }}</strong></td>
            <td width="25" height="25" text-align="center" valign="middle"><strong>{{ $totals['estimate_appt_schedule'] }}</strong></td>
            <td width="20" height="25" text-align="center" valign="middle"><strong>{{ $totals['lost_request'] }}</strong></td>
        </tr>
    </tfoot>
</table>

@php
    $totalList = collect($orderLists['total'] ?? []);
    $qualifiedList = collect($orderLists['qualified'] ?? []);
    $estimateList = collect($orderLists['estimate_appt_schedule'] ?? []);
    $lostRequestList = collect($orderLists['lost_request'] ?? []);
@endphp

<table>
  <thead>
    <tr>
      <td colspan="3" style="font-weight: bold; font-size: 14px; background-color: #f0f0f0;">Total Orders List</td>
    </tr>
    <tr>
      <th width="45">Order</th>
      <th width="20">Created Date</th>
      <th width="35">Current Status</th>
    </tr>
  </thead>
  <tbody>
    @if ($totalList->isEmpty())
      <tr>
        <td colspan="3">No orders for the selected dates.</td>
      </tr>
    @else
      @foreach ($totalList as $order)
        <tr>
          <td>{{ !empty($order['name']) ? '#' . $order['id'] . ' - ' . $order['name'] : '#' . $order['id'] }}</td>
          <td>{{ $order['created_date'] ?? '-' }}</td>
          <td>{{ $order['current_status'] ?? '-' }}</td>
        </tr>
      @endforeach
    @endif
  </tbody>
</table>

<table>
  <thead>
    <tr>
      <td colspan="3" style="font-weight: bold; font-size: 14px; background-color: #f0f0f0;">Qualified Orders List</td>
    </tr>
    <tr>
      <th width="45">Order</th>
      <th width="20">Created Date</th>
      <th width="35">Current Status</th>
    </tr>
  </thead>
  <tbody>
    @if ($qualifiedList->isEmpty())
      <tr>
        <td colspan="3">No qualified orders for the selected dates.</td>
      </tr>
    @else
      @foreach ($qualifiedList as $order)
        <tr>
          <td>{{ !empty($order['name']) ? '#' . $order['id'] . ' - ' . $order['name'] : '#' . $order['id'] }}</td>
          <td>{{ $order['created_date'] ?? '-' }}</td>
          <td>{{ $order['current_status'] ?? '-' }}</td>
        </tr>
      @endforeach
    @endif
  </tbody>
</table>

<table>
  <thead>
    <tr>
      <td colspan="5" style="font-weight: bold; font-size: 14px; background-color: #f0f0f0;">Lost Request Orders List</td>
    </tr>
    <tr>
      <th width="35">Order</th>
      <th width="15">Created Date</th>
      <th width="15">Lost Request Date</th>
      <th width="20">Current Status</th>
      <th width="35">Loss Reason</th>
    </tr>
  </thead>
  <tbody>
    @if ($lostRequestList->isEmpty())
      <tr>
        <td colspan="5">No lost request orders for the selected dates.</td>
      </tr>
    @else
      @foreach ($lostRequestList as $order)
        <tr>
          <td>{{ !empty($order['name']) ? '#' . $order['id'] . ' - ' . $order['name'] : '#' . $order['id'] }}</td>
          <td>{{ $order['created_date'] ?? '-' }}</td>
          <td>{{ $order['status_date'] ?? '-' }}</td>
          <td>{{ $order['current_status'] ?? '-' }}</td>
          <td>{{ $order['loss_reason_frontdesk'] ?? '-' }}</td>
        </tr>
      @endforeach
    @endif
  </tbody>
</table>

<table>
  <thead>
    <tr>
      <td colspan="3" style="font-weight: bold; font-size: 14px; background-color: #f0f0f0;">Estimate &amp; Appt Schedule Orders List</td>
    </tr>
    <tr>
      <th width="45">Order</th>
      <th width="20">Created Date</th>
      <th width="35">Current Status</th>
    </tr>
  </thead>
  <tbody>
    @if ($estimateList->isEmpty())
      <tr>
        <td colspan="3">No estimate &amp; appt schedule orders for the selected dates.</td>
      </tr>
    @else
      @foreach ($estimateList as $order)
        <tr>
          <td>{{ !empty($order['name']) ? '#' . $order['id'] . ' - ' . $order['name'] : '#' . $order['id'] }}</td>
          <td>{{ $order['created_date'] ?? '-' }}</td>
          <td>{{ $order['current_status'] ?? '-' }}</td>
        </tr>
      @endforeach
    @endif
  </tbody>
</table>
