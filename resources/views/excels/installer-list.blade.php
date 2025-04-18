<table>
    <thead>
      <tr>
        <td colspan="4" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;" >
            Payments Installer by : {{$installer}}
        </td>
        <td colspan="4" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;" >
            Company Name : {{$company}}
        </td>
         <td colspan="4" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;" >
        Biweekly : {{$biweekly}}
        </td>
      
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
      @foreach($orders as $order)
        @php
              
            if($order['installation_payments']->count()>=1){
            $percentegePayment = (int)$order['installation_payments'][$order['installation_payments']->count() - 1]['percentage_payment'];
            $installerPayment = 0;
            
            $pendingPaymentAmount = (float) $order['pending_payment_amount'];
            $totalPaymentAmount = (float)$order['total_payment_amount'];
              
            $totalExtraWork += (int)$order['installation_payments'][$order['installation_payments']->count() - 1]['extra_work'];
            $otherCostInstaller += (int)$order['installation_payments'][$order['installation_payments']->count() - 1]['other_cost_installer'];
            $totalExtraDiscount += (int)$order['installation_payments'][$order['installation_payments']->count() - 1]['extra_discount'];
            $grandTotalPayment += $totalPaymentAmount;
            $grandtotalPending += $pendingPaymentAmount;
            }
        @endphp
          
            
        @if ($order['installation_payments']->count()>=1)
        <tr>
            <td width='20' height='25' text-align='left' valign='middle'>{{ $order['installation_date'] }}</td>
            <td width='20' height='25' text-align='center' valign='middle'>{{$order['inspection_installation_date'] }}</td>
            <td width='20' height='25' text-align='center' valign='middle'>{{$order['final_installation_date'] }}</td>
            <td width='50' height='25' text-align='left' valign='middle'>{{ $order['name'] }}</td>
            <td width='20' height='25' text-align='left' valign='middle'>
              @foreach ($order['owners'] as $owner)
                {{ $owner['name'] }} <br/>
                
              @endforeach
              
              
             </td>
             <td width='20' height='25' text-align='left' valign='middle'>{{$order['supervisor'] }}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{$order['city_permits'] ? 'YES' : 'NO' }}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($order['amount'], 2, '.', ',')}}</td>
             <td width='20' height='25' text-align='center' valign='middle'> {{  number_format($order['installation_payments'][$order['installation_payments']->count() - 1]['percentage_payment'], 2, '.', ',') . '%'}}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($order['installation_payments'][$order['installation_payments']->count() - 1]['installer_payment'], 2, '.', ',')}}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($pendingPaymentAmount, 2, '.', ',')}}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($order['installation_payments'][$order['installation_payments']->count() - 1]['extra_work'], 2, '.', ',')}}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{$order['installation_payments'][$order['installation_payments']->count() - 1]['responsible_extra_work'] ?? '' }}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($order['installation_payments'][$order['installation_payments']->count() - 1]['extra_discount'], 2, '.', ',')}}</td>
             <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($order['installation_payments'][$order['installation_payments']->count() - 1]['other_cost_installer'], 2, '.', ',')}}</td>
            <td width='20' height='25' text-align='center' valign='middle'>{{ '$' . number_format($totalPaymentAmount, 2, '.', ','); }}</td>
            <td width='20' height='25' text-align='center' valign='middle'>{{collect([
                                                          $order['partial_payment_installation'] ? 'PARTIAL' : '',
                                                          $order['final_payment_installation'] ? 'FINAL' : '',
                                                      ])->filter()->join(' , ') }}</td>
            <td width='20' height='25' text-align='center' valign='middle'>{{ $order['notes']?? '' }}</td>
            <td width='20' height='25' text-align='center' valign='middle'>{{ collect([
                                                          $order['pre_inspection'] ? 'PI' : '',
                                                          $order['walk_trough'] ? 'WT' : '',
                                                          $order['inspection'] ? 'IN' : '',
                                                      ])->filter()->join(' , ') }} </td>
            <td width='20' height='25' text-align='center' valign='middle'>{{ $order['payment_extra_fields']['installer_payment_status'] ?? '' }}</td>
           
        </tr>
        @endif
      @endforeach
    
    @php
      //dd($pendingPaymentAmount);
    @endphp
    </tbody>
    <tfoot>
        <tr>
            <!-- Celdas vacías para alinear las columnas -->
            <td colspan="10" style="font-weight: bold; text-align: right;">Total:</td>
       
             <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;"> {{ '$' . number_format($grandtotalPending, 2, '.', ',') }}</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($totalExtraWork, 2, '.', ',') }}</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;"></td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($totalExtraDiscount, 2, '.', ',') }}</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($otherCostInstaller, 2, '.', ',') }}</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($grandTotalPayment, 2, '.', ',') }}</td>
            
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>