<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Biweekly Extra Work Summary</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 16px;
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

    <h2>Biweekly Extra Work Summary</h2>

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
          <th width='50'>Total Extra Work</th>
          <th width='50'>Resposible Extra Work</th>
          <th width='50'>Remark</th>

        
      </tr>
    </thead>

    <tbody>
  

    {{-- Cálculos --}}
    @php
        /*$totalPendingPaymentAmount = 0;
        $totalExtraWork = 0;
        $totalExtraDiscount = 0;
        $totalPaymentProcessed = 0;
        $otherCostInstaller = 0;
        $grandTotalPayment = 0;
        $grandtotalPending = 0;
        $totalPaymentTotal = 0;*/
        //dd($extraworks);
        $totalExtarWork = 0;
    @endphp

    @foreach($extraworks as $extrawork)
  @php
     //dd($extrawork);
     $totalExtarWork+=$extrawork->extra_work;
     /*$totalPendingPaymentAmount = 0;
     $companyName = $biweekly['data'][0]['company_name'] ?? '';
    $installerName = $biweekly['data'][0]['installer'] ?? '';*/
  @endphp
            <tr>
            <td>{{ $extrawork->order->name}}</td>
            <td>{{$extrawork->installationTeam->name}}</td>
            <td>
               @foreach ($extrawork->order->owners as $owner) 
                {{ $owner->name}} <br/>
              @endforeach
            </td>
            <td>{{$extrawork->order->supervisor->name}}</td>
            <td>{{ '$' . number_format($extrawork->extra_work, 2, '.', ',') }}</td>
            <td>{{$extrawork->responsible_extra_work}}</td>
            <td>{{$extrawork->notes}}</td>
        </tr>
         @php
         // dd( $totalPaymentTotal);
          // $grandTotalPayment += $totalPaymentTotal;
          // $grandtotalPending += $totalPendingPaymentAmount;
          

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
            <!-- Celdas vacías para alinear las columnas -->
            <td colspan="4" style="font-weight: bold; text-align: right;">Total:</td>
            <td width='20' height='25' text-align='center' valign='middle' style="font-weight: bold;">{{ '$' . number_format($totalExtarWork, 2, '.', ',') }}</td>
            
            <td colspan="2"></td>
        </tr>
    </tfoot>
      </table>

 

</body>
</html>
