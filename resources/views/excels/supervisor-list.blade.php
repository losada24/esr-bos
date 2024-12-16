<table>
    <thead>
      <tr>
        <td colspan="4" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;" >
            Project Supervisions by : {{ $supervisor['name'] }}
        </td>
    </tr>

    <!-- Espacio adicional (opcional) -->
    <tr></tr>
    <tr></tr>
      <tr>
          <th width='50'>Project</th>
          <th width='50'>City</th>
          <th width='20'>City Permit</th>
          <th width='50'>Owners</th>
          <th width='50'>Installers</th>
          <th width='50'>Month</th>
          <th width='50'>Start Date</th>
          <th width='50'>End Date</th>
          <th width='50'>Planning Date</th>
          <th width='50'>Qty Date</th>
          <th width='50'>Value Project</th>
          <th width='50'>% Commissions</th>
          <th width='50'>Commissions</th>
          <th width='50'>Status</th>
          <th width='50'>Date Paid</th>
          
      </tr>
    </thead>
    <tbody>
      @foreach($orders as $order)
        <tr>
            <td width='50' height='25' text-align='left' valign='middle'>{{ $order['name'] }}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{$order['city'] }}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{$order['city_permits'] ? 'YES' : '' }}</td>
             <td width='20' height='25' text-align='center' valign='middle'>
              @foreach ($order['owners'] as $owner)
                {{ $owner['name'] }} <br/>
              @endforeach
             </td>
             <td width='20' height='25' text-align='center' valign='middle'>
                @foreach ($order['installation_team'] as $installation_team)
                  {{ $installation_team['company_name'] }} <br/>
                @endforeach
             </td>
             <td width='20' height='25' text-align='center' valign='middle'>{{$order['month'] }}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{$order['installation_date'] }}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{$order['final_installation_date'] }}</td>
              <td width='20' height='25' text-align='center' valign='middle'>{{$order['execution_planing_date'] }}</td>
              <td width='20' height='25' text-align='center' valign='middle'>{{$order['qty_days'] }}</td>
              <td width='20' height='25' text-align='center' valign='middle'>{{'$' . number_format($order['project_amount'], 2, '.', ',') }}</td>
              <td width='20' height='25' text-align='center' valign='middle'>{{$order['supervisor_payment_percentage']}} % </td>
              <td width='20' height='25' text-align='center' valign='middle'>{{'$' . number_format($order['supervisor_commissions'], 2, '.', ',') }}</td>
              <td width='20' height='25' text-align='center' valign='middle'>{{$order['supervisor_payment_status'] }}</td>
              <td width='20' height='25' text-align='center' valign='middle'>{{$order['supervisor_payment_date'] }}</td>
           
        </tr>
      @endforeach
    </tbody>
</table>