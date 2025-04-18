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
       @php
            $totalProjectAmount = 0; // Inicializar suma de Value Project
            $totalCommissions = 0; // Inicializar suma de Commissions
        @endphp
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
              <td width='20' height='25' text-align='center' valign='middle'>{{$order['project_amount'] }}</td>
              <td width='20' height='25' text-align='center' valign='middle'>{{$order['supervisor_payment_percentage']}} % </td>
              <td width='20' height='25' text-align='center' valign='middle'>{{ $order['supervisor_commissions'] }}</td>
              <td width='20' height='25' text-align='center' valign='middle'>{{$order['supervisor_payment_status'] }}</td>
              <td width='20' height='25' text-align='center' valign='middle'>{{$order['supervisor_payment_date'] }}</td>
           
        </tr>
        @php
                // Acumular valores para las sumas totales
                $totalProjectAmount += $order['project_amount'];
                $totalCommissions += $order['supervisor_commissions'];
            @endphp
      @endforeach
    </tbody>
    <tfoot>
        <tr>
            <!-- Celdas vacías para alinear las columnas -->
            <td colspan="10" style="font-weight: bold; text-align: right;">Total:</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">
                {{ '$' . number_format($totalProjectAmount, 2, '.', ',') }}
            </td>
            <td></td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">
                {{ '$' . number_format($totalCommissions, 2, '.', ',') }}
            </td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>