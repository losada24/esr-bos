<style>
    /* Centrar la tabla en la página */
    .table-container {
        display: flex;
        justify-content: center;
        padding: 20px;
    }

    /* Agregar scroll horizontal y vertical */
    .table-wrapper {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 400px;
    }

    /* Estilo general de la tabla */
    table {
        width: 100%;
        max-width: 1200px; /* Puedes ajustar este valor si necesitas más ancho */
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size:18px;
    }

    /* Bordes y alineación de celdas */
    th, td {
        border: 1px solid #000;
        padding: 8px;
        text-align: center;
        vertical-align: middle;
    }

    /* Encabezados */
    th {
        background-color: #f0f0f0;
        font-weight: bold;
    }

    /* Alternar colores de filas */
    tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    /* Total en negrita */
    tfoot td {
        font-weight: bold;
        background-color: #e0e0e0;
    }

    /* Títulos principales */
    .header-info {
        font-weight: bold;
        font-size: 18px;
        text-align: left;
        background-color: #dcdcdc;
        padding: 10px;
    }

</style>
<div class="table-container">
<div class="table-wrapper">
<table>
    <thead>
      <tr>
        <td colspan="4" style="font-weight: bold; font-size: 20px; text-align: left; background-color: #f0f0f0;" >
            Payments Installer by : {{$installer}}
        </td>
        <td colspan="4" style="font-weight: bold; font-size: 20px; text-align: left; background-color: #f0f0f0;" >
            Company Name : {{$company}}
        </td>
         <td colspan="4" style="font-weight: bold; font-size: 20px; text-align: left; background-color: #f0f0f0;" >
            Biweekly : {{$biweeklyTitle}}
        </td>
        <td colspan="8" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;"></td>
      
    </tr>
    

    <!-- Espacio adicional (opcional) -->
    <tr></tr>
    <tr></tr>
      <tr>
          <th width='20'>Start Date</th>
          <th width='20'>Pre-Inspection Date</th>
          <th width='20'>End Date</th>
          <th width='50'>Name</th>
          <th width='20'>Owners</th>
          <th width='20'>Supervisor</th>
          <th width='50'>City Permit</th>
          <th width='50'>Total Project Payment</th>
          <th width='50'>% Project</th>
          <th width='50'>Payment Processed</th>
          <th width='50'>Pending Pay</th>
          <th width='50'>Extra Work</th>
          <th width='50'>Responsible Extra Work</th>
          <th width='50'>Extra Discount</th>
          <th width='50'>Other Cost</th>
           <th width='50'>Total Payment</th>
          <th width='50'>Collected Payment</th>
          <th width='50'>Remarks</th>
          <th width='50'>Delivered Documents</th>
           <th width='50'>Status Payment</th>
      </tr>
    </thead>
    <tbody>
       @php
            $totalPendingPaymentAmount = 0; // Inicializar suma de Value Project
            $totalExtraWork = 0; // Inicializar suma de Commissions
            $totalExtraDiscount = 0; // Inicializar suma de Commissions
            $totalPaymentProcessed = 0; // Inicializar suma de Commissions
            $otherCostInstaller = 0;
            $grandTotalPayment = 0;
            $grandtotalPending = 0;
           /* if (count($payments) > 0) {
              $totalPaymentProcessed = $payments[0]['amount'];
            }*/
        @endphp
      @foreach($biweeklys as $biweekly)
        @php
              // Acumular valores para las sumas totales
              // $totalProjectAmount += $order['project_amount'];
              // $totalCommissions += $order['supervisor_commissions'];
              $lastPaymentPercent = count($biweekly['installation_payments']) > 0 ? count($biweekly['installation_payments']) - 1 : 0;
              $percentegePayment = (float)($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['percentage_payment'] ?? 0);
              $installerPayment = 0;
              /* if ($loop->index > 0) {
                dd($biweekly);
              } */

              if (count($biweekly['installation_payments']) > 0) {

                 foreach ($biweekly['installation_payments'] as $installation_payments) {
                    $installerPayment += $installation_payments['installer_payment'];
                }
              }
             
             
              //$installerPayment = $payments->where('order_id', $payment['order_id'])->where('id', '<=', $payment['id'])->sum('installer_payment');
              $pendingPaymentAmount = (float) $biweekly['amount'] - (float) $installerPayment;
             
              //$totalPendingPaymentAmount +=  $payment['installer_payment'];
              $totalPaymentAmount = (float)($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['installer_payment'] ?? 0) + 
                (float)($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['extra_work'] ?? 0) - 
                (float)($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['extra_discount'] ?? 0) + 
                (float)($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['other_cost_installer'] ?? 0);
      

              $totalExtraWork += (float)($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['extra_work'] ?? 0);
              $otherCostInstaller += (float)($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['other_cost_installer'] ?? 0);
              $totalExtraDiscount += (float)($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['extra_discount'] ?? 0);
              $grandTotalPayment += $totalPaymentAmount;
              $grandtotalPending += $pendingPaymentAmount;
              
              //dd( $percentegePayment, $totalPaymentAmount, $totalExtraWork, $otherCostInstaller, $totalExtraDiscount, $grandTotalPayment, $grandtotalPending);
          @endphp
        <tr>
            <td width='20' height='25' text-align='left' valign='middle'>{{ $biweekly['installation_date'] }}</td>
            <td width='20' height='25' text-align='center' valign='middle'>{{$biweekly['inspection_installation_date'] }}</td>
            <td width='20' height='25' text-align='center' valign='middle'>{{$biweekly['final_installation_date'] }}</td>
            <td width='50' height='25' text-align='left' valign='middle' 
              @if(number_format($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['percentage_payment'] ?? 0, 2, '.', ',') === '0.00')
                  style="background-color: #FFFF00;"
                  
              @endif
          >
              {{ $biweekly['name']}}
             
          </td>
            <td width='20' height='25' text-align='left' valign='middle'>
              @foreach ($biweekly['owners'] as $owner)
                {{ $owner['name'] }} <br/>
              @endforeach
              
              
             </td>
             <td width='20' height='25' text-align='left' valign='middle'>{{$biweekly['supervisor'] }}</td>
              
             <td width='20' height='25' text-align='center' valign='middle'>{{$biweekly['city_permits'] ? 'YES' : 'NO' }}</td>
             
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($biweekly['amount'], 2, '.', ',')}}</td>
              
             
             <td width='20' height='25' text-align='center' valign='middle'> {{  number_format($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['percentage_payment'] ?? 0, 2, '.', ',') . '%'}}</td>
            
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['installer_payment'] ?? 0, 2, '.', ',')}}</td>
             
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($pendingPaymentAmount, 2, '.', ',')}}</td>
             
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['extra_work'] ?? 0, 2, '.', ',')}}</td>
              
             <td width='20' height='25' text-align='center' valign='middle'>{{$biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['responsible_extra_work'] ?? '' }}</td>
            
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['extra_discount'] ?? 0, 2, '.', ',')}}</td>
              
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]['other_cost_installer'] ?? 0, 2, '.', ',')}}</td>
            
            <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($totalPaymentAmount ?? 0, 2, '.', ','); }}</td>
            
            <td width='20' height='25' text-align='center' valign='middle'>{{collect([
                                                          $biweekly['partial_payment_installation'] ? 'PARTIAL' : '',
                                                          $biweekly['final_payment_installation'] ? 'FINAL' : '',
                                                      ])->filter()->join(' , ') }}</td>
                                                      
            <td width='20' height='25' text-align='center' valign='middle'>{{ $biweekly['notes']?? '' }}</td>
            
            <td width='20' height='25' text-align='center' valign='middle'>{{ collect([
                                                          $biweekly['pre_inspection'] ? 'PI' : '',
                                                          $biweekly['walk_trough'] ? 'WT' : '',
                                                          $biweekly['inspection'] ? 'IN' : '',
                                                      ])->filter()->join(' , ') }} </td>
                                               
                                                   
            <td width='20' height='25' text-align='center' valign='middle'>{{ $biweekly['payment_extra_fields']['installer_payment_status'] ?? '' }}</td>   
        </tr>
        
      @endforeach
    </tbody>
    <tfoot>
        <tr>
            <!-- Celdas vacías para alinear las columnas -->
            <td colspan="10" style="font-weight: bold; text-align: right;">Total:</td>

          <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($grandtotalPending ?? 0, 2, '.', ',') }}</td>
         
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($totalExtraWork ?? 0, 2, '.', ',') }}</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;"></td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($totalExtraDiscount ?? 0,  2, '.', ',') }}</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($otherCostInstaller ?? 0, 2, '.', ',') }}</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($grandTotalPayment ?? 0, 2, '.', ',') }}</td>

            <td colspan="4"></td>
        </tr>
    </tfoot>
</table>
</div>
</div>