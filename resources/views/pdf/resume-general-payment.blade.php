<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments Summary</title>
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

    <h2>Biweekly Payment Summary</h2>

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
          <th width='20'>Company Name</th>
          <th width='20'>Installer Name</th>
          <th width='50'>Pending Pay</th>
           <th width='50'>Total Payment</th>
        
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
     //dd($biweekly['data']);
     $totalPaymentTotal = 0;
     $totalPendingPaymentAmount = 0;
     $companyName = $biweekly['data'][0]['company_name'] ?? '';
    $installerName = $biweekly['data'][0]['installer'] ?? '';
  @endphp
        @foreach ( $biweekly['data'] as $biweeklydata )
          
            @php
           //dd($biweekly['data'] );
            $totalPaymentTotal += $biweeklydata['total_payment_amount'];
            $totalPendingPaymentAmount+= $biweeklydata['pending_payment_amount'];
            @endphp
          
        @endforeach
            <tr>
            <td>{{ $companyName }}</td>
            <td>{{ $installerName}}</td>
            <td>{{ '$' . number_format( $totalPendingPaymentAmount, 2, '.', ',') }}</td>
            <td>{{ '$' . number_format($totalPaymentTotal, 2, '.', ',') }}</td>
        </tr>
         @php
         // dd( $totalPaymentTotal);
           $grandTotalPayment += $totalPaymentTotal;
           $grandtotalPending += $totalPendingPaymentAmount;
          

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

      </table>

    {{-- Tabla de totales --}}
  <h2>Biweekly Payment Grand Total</h2>
    <table class="totals-table">
        
        <tr>
            <td class="totals-label" colspan="1">Grand Total Pending Payment</td>
            <td class="amount">{{ '$' . number_format($grandtotalPending, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td class="totals-label" colspan="1">Grand Total Payment</td>
            <td class="amount">{{ '$' . number_format($grandTotalPayment, 2, '.', ',') }}</td>
        </tr>
    </table>

</body>
</html>
