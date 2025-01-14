@php
  // $fmt = new NumberFormatter( 'us_US', NumberFormatter::CURRENCY );
  $extraWorksCollection = collect();
  $grandTotal = 0;
@endphp
<html>
<header>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <link rel="stylesheet" href="{{ base_path('resources/css/pdf-styles.css') }}">
  <style>
    .page-break {
        page-break-after: always;
    }
  </style>
</header>
<body>
    <header class="clearfix">
      <div id="logo">
        <img src="{{ base_path('resources/assets/images/logo-reylos.jpg') }}">
      </div>
      <h1>PAYMENT LIST {{ strtoupper($order->typeOfWork->name) }}</h1>
      <div id="company" class="clearfix">
        <div><span>DATE</span> {{ Carbon\Carbon::parse($order->installation_date)->format('m/d/Y') }}</div>
      </div>
      <div id="project">
        <div><span>ORDER NAME</span> {{ $order->name }}</div>
        <div><span>CLIENT PHONE</span> {{ $order->client->phone }}</div>
        <div><span>ADDRESS</span>{{ 
                        ($order->job_address ?? '') .
                        (!empty($order->city) ? ', ' . $order->city : '') .
                        (!empty($order->job_state) ? ', ' . $order->job_state : '') .
                        (!empty($order->job_zip) ? ', ' . $order->job_zip : '') 
                    }}<div>
        <div><span>COLOR</span> {{ $order->frame_color}}</div>
      </div>
    </header>
      <main>
        <table class='info-table'>
          <thead>
            <tr>
              <th class="service">PRODUCT</th>
              <th class="desc">CATEGORY</th>
              <th class="number-headers">QTY</th>
              <th class="number-headers">PRICE</th>
              <th class="number-headers">SUBTOTAL</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($order->orderProducts->groupBy('productCategory.name') as $key => $products)
              <tr>
                <td class="table-section" colspan="5">{{ $key }}</td>
              </tr>
              @foreach ($products as $product)
                @php
                  $extraWorksCollection = $extraWorksCollection->merge($product->orderProductExtraWorks);
                  $grandTotal += $product->total_price;
                @endphp
                <tr>
                  <td class="service">&nbsp;</td>
                  <td class="desc">
                    {{ $product->productConfig->name }}
                    @if ($product->installation_other_level) 
                       (Other Level) 
                    @endif
                  </td>
                  <td class="qty">{{ $product->qty }}</td>
                  <td class="unit">{{ '$' . number_format($product->unit_price, 2, '.', ',') /*$fmt->formatCurrency($product->unit_price, 'USD')*/ }}</td>
                  <td class="total">{{ '$' . number_format($product->total_price, 2, '.', ',') /* $fmt->formatCurrency($product->total_price, 'USD')*/ }}</td>
                </tr>
              @endforeach
            @endforeach
            <tr>
              <td class="table-section" colspan="5">Extra Works</td>
            </tr>
           
            @foreach ($extraWorksCollection->groupBy('name') as $key => $extraWork)
              @php 
                $count = $extraWork->sum('pivot.amount');
                $price = $extraWork->first()->price;
                $total = $price  * $count;
                $grandTotal += $total;
              @endphp
              <tr>
                <td class="service" colspan="2">{{ $key }}</td>
                <td class="qty">{{ $count }}</td>
                <td class="unit">{{ '$' . number_format($price, 2, '.', ',') /* $fmt->formatCurrency($price, 'USD')*/ }}</td>
                <td class="total">{{ '$' . number_format($total, 2, '.', ',')/* $fmt->formatCurrency($total, 'USD')*/ }}</td>
              </tr>
            @endforeach
            <tr>
              <td class="service" colspan="4">Travel Cost</td>
              <td class="total">{{ '$' . number_format($order->travelCost->price, 2, '.', ',') /* $fmt->formatCurrency($order->travelCost->price, 'USD' )*/ }}</td>
            </tr>
            <tr>
              <td class="service" colspan="4">Other Cost</td>
              <td class="total">{{ '$' . number_format($order->additional_travel_costs, 2, '.', ',') /* $fmt->formatCurrency($order->additional_travel_costs, 'USD' ) */}}</td>
            </tr>
            <tr>
              <td class='order-notes' colspan="3">
                @if ($order->notes != '')
                  <div class="notes">
                    <strong>Notes:</strong>
                    <p>{{ $order->notes }}</p>
                  </div>
                @endif
              </td>
              <td class='order-notes' colspan='2'>
                <table class='summary-table'>
                  <tr>
                    <td colspan='2' class="total border-right">Total</td>
                    <td class="total">{{ '$' . number_format($grandTotal + $order->travelCost->price + $order->additional_travel_costs, 2, '.', ',') /* $fmt->formatCurrency($grandTotal + $order->travelCost->price + $order->additional_travel_costs, 'USD' ) */ }}</td>
                  </tr>
                  <tr>
                    <td class="other-services border-right">After Installation</td>
                    <td class="grand total border-right">
                      @if ($order->city_permits) 
                        {{ $order->initial_payment_percentage }}%
                      @endif
                    </td>
                    <td class="grand total">
                      @if ($order->city_permits) 
                        {{ '$' . number_format(($grandTotal + $order->travelCost->price + $order->additional_travel_costs) * $order->initial_payment_percentage / 100, 2, '.', ',') }}
                      @endif
                    </td>
                  </tr>
                  <tr>
                    <td class="other-services border-right">After Inspection</td>
                    <td class="grand total border-right">
                      @if ($order->city_permits) 
                        {{ 100 - $order->initial_payment_percentage }}%
                      @endif
                    </td>
                    <td class="grand total">
                      @if ($order->city_permits) 
                        {{ '$' . number_format(($grandTotal + $order->travelCost->price + $order->additional_travel_costs) * (100 - $order->initial_payment_percentage) / 100, 2, '.', ',') }}
                      @endif
                    </td>
                  </tr>
                  <tr>
                    <td colspan='2' class="grand total border-right">Total</td>
                    <td class="grand total">{{ '$' . number_format($grandTotal + $order->travelCost->price + $order->additional_travel_costs, 2, '.', ',') /* $fmt->formatCurrency($grandTotal + $order->travelCost->price + $order->additional_travel_costs, 'USD' ) */ }}</td>
                  </tr>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
        <div id="notices">
          <p><strong>Installation include:</strong> Protection, Remove and Dispousal existing Window & Doors, Wood Bucks, Screws, Caulking, Ready for Inspection, Screw Covers, and Cleaning.</p>
          <p><strong>Note:</strong> Payments after installation can take 1 to 2 Weeks maximun.</p>
        </div>
      </main>
      <footer>
        
      </footer>
  </body>
</html>
