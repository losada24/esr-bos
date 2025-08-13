<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments Summary</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 25px;
            color: #333;
            margin: 40px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #1a1a1a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            padding: 8px 10px;
            border: 1px solid #ccc;
        }

        th {
            background-color: #f0f0f0;
            text-align: left;
            font-weight: bold;
        }

        .summary-table th {
            background-color: #f0f0f0;
        }

        .totals-table td {
            font-weight: bold;
            text-align: left;
        }

        .totals-table td.amount {
            text-align: left;
            background-color: #f0f0f0;
        }

        .totals-label {
            background-color: #fafafa;
        }
    </style>
</head>
<body>

    <h2>Uncollected Payments Report</h2>

    {{-- Tabla de resumen de datos --}}
    <table class="summary-table">
        <tr>
            <th>Biweekly</th>
            <td>{{ $biweeklyTitle }}</td>
        </tr>
    </table>

      <table class="summary-table">
      <thead>
       <tr>
           <th width='20'>Project Name</th>
          <th width='20'>Installer Name</th>
          <th width='50'>Owner Name</th>
          <th width='50'>Supervisor Name</th>
          <th width='50'>% Project</th>
          <th width='50'>Payments</th>
           <th width='50'>Collected Payment</th>
        
      </tr>
    </thead>

    <tbody>
  

    {{-- Cálculos --}}
    @php
        $totalPendingPaymentAmount = 0;
        $totalExtraWork = 0;
        $totalExtraDiscount = 0;
        $totalPaymentProcessed = 0;
        $otherCostInstaller = 0;
        $grandTotalPayment = 0;
        $grandtotalPending = 0;
        $totalPaymentTotal = 0;
        //dd($biweeklys);
    @endphp

    @foreach($biweeklys as $biweekly)
  @php
     //dd($biweekly);
     $totalPaymentTotal+= $biweekly['total_payment_amount'];
     $totalPendingPaymentAmount = 0;
     $companyName = $biweekly['data'][0]['company_name'] ?? '';
    $installerName = $biweekly['data'][0]['installer'] ?? '';
  @endphp
      
            <tr>
            <td>{{ $biweekly['name']}}</td>
            <td>{{$biweekly['installer'] }}</td>
            <td>
             @foreach ($biweekly['owners'] as $owner) 
                {{ $owner['name']}} <br/>
              @endforeach
            </td>
            <td>{{ $biweekly['supervisor'] }}</td>
             <td>
             @php
                    $lastPayment = count($biweekly['installation_payments']) > 0 
                        ? $biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]
                        : null;
                @endphp

                @if ($lastPayment)
                    {{ number_format($lastPayment['percentage_payment'], 2, '.', ',') . '%' }}
                @else
                    N/A
                @endif
            </td>
              <td>{{  '$' . number_format($biweekly['total_payment_amount'] , 2, '.', ',' )}}
              </td>
              <td>
                  {{collect([
                        $biweekly['partial_payment_installation'] ? 'PARTIAL' : '',
                        $biweekly['final_payment_installation'] ? 'FINAL' : '',
                    ])->filter()->join(' , ') }}
              </td>
        </tr>
         @php
         // dd( $totalPaymentTotal);
          
          

            /*$payments = $biweekly['installation_payments'];
            $lastPayment = end($payments);

            $installerPayment = 0;
            foreach ($payments as $payment) {
                $installerPayment += $payment['installer_payment'];
            }

            $pendingPaymentAmount = (float) $biweekly['amount'] - (float) $installerPayment;
            $totalPaymentAmount =
                (int) $lastPayment['installer_payment'] +
                (int) $lastPayment['extra_work'] -
                (int) $lastPayment['extra_discount'] +
                (int) $lastPayment['other_cost_installer'];

            $totalExtraWork += (int) $lastPayment['extra_work'];
            $otherCostInstaller += (int) $lastPayment['other_cost_installer'];
            $totalExtraDiscount += (int) $lastPayment['extra_discount'];
            $grandTotalPayment += $totalPaymentAmount;
            $grandtotalPending += $pendingPaymentAmount;*/


        @endphp

       
    @endforeach

    </tbody>
    <tfoot>
        <tr>
            

        <!-- Ajustamos colspan a 4 → ahora se convierte en 3 -->
            <td colspan="5" style="font-weight: bold; text-align: right;">Total:</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($totalPaymentTotal, 2, '.', ',') }}</td>
            
            <td></td>
        </tr>
    </tfoot>

      </table>
       <table class="summary-table">
      <thead>
       <tr>
           <th width='20'>Project Name</th>
          <th width='20'>Installer Name</th>
          <th width='50'>Owner Name</th>
          <th width='50'>Supervisor Name</th>
          <th width='50'>% Project</th>
          <th width='50'>Payments</th>
           <th width='50'>Collected Payment</th>
        
      </tr>
    </thead>

    <tbody>
  

    {{-- Cálculos --}}
    @php
        $totalPendingPaymentAmount = 0;
        $totalExtraWork = 0;
        $totalExtraDiscount = 0;
        $totalPaymentProcessed = 0;
        $otherCostInstaller = 0;
        $grandTotalPayment = 0;
        $grandtotalPending = 0;
        $totalPaymentTotal = 0;
        //dd($biweeklys);
    @endphp

    @foreach($biweeklys1 as $biweekly)
  @php
     //dd($biweekly);
     $totalPaymentTotal+= $biweekly['total_payment_amount'];
     $totalPendingPaymentAmount = 0;
     $companyName = $biweekly['data'][0]['company_name'] ?? '';
    $installerName = $biweekly['data'][0]['installer'] ?? '';
  @endphp
      
            <tr>
            <td>{{ $biweekly['name']}}</td>
            <td>{{$biweekly['installer'] }}</td>
            <td>
             @foreach ($biweekly['owners'] as $owner) 
                {{ $owner['name']}} <br/>
              @endforeach
            </td>
            <td>{{ $biweekly['supervisor'] }}</td>
             <td>
             @php
                    $lastPayment = count($biweekly['installation_payments']) > 0 
                        ? $biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]
                        : null;
                @endphp

                @if ($lastPayment)
                    {{ number_format($lastPayment['percentage_payment'], 2, '.', ',') . '%' }}
                @else
                    N/A
                @endif
            </td>
              <td>{{  '$' . number_format($biweekly['total_payment_amount'] , 2, '.', ',' )}}
              </td>
              <td>
                  {{collect([
                        $biweekly['partial_payment_installation'] ? 'PARTIAL' : '',
                        $biweekly['final_payment_installation'] ? 'FINAL' : '',
                    ])->filter()->join(' , ') }}
              </td>
        </tr>
         @php
         // dd( $totalPaymentTotal);
          
          

            /*$payments = $biweekly['installation_payments'];
            $lastPayment = end($payments);

            $installerPayment = 0;
            foreach ($payments as $payment) {
                $installerPayment += $payment['installer_payment'];
            }

            $pendingPaymentAmount = (float) $biweekly['amount'] - (float) $installerPayment;
            $totalPaymentAmount =
                (int) $lastPayment['installer_payment'] +
                (int) $lastPayment['extra_work'] -
                (int) $lastPayment['extra_discount'] +
                (int) $lastPayment['other_cost_installer'];

            $totalExtraWork += (int) $lastPayment['extra_work'];
            $otherCostInstaller += (int) $lastPayment['other_cost_installer'];
            $totalExtraDiscount += (int) $lastPayment['extra_discount'];
            $grandTotalPayment += $totalPaymentAmount;
            $grandtotalPending += $pendingPaymentAmount;*/


        @endphp

       
    @endforeach

    </tbody>
    <tfoot>
        <tr>
            

        <!-- Ajustamos colspan a 4 → ahora se convierte en 3 -->
            <td colspan="5" style="font-weight: bold; text-align: right;">Total:</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($totalPaymentTotal, 2, '.', ',') }}</td>
            
            <td></td>
        </tr>
    </tfoot>

      </table>

</body>
</html>
