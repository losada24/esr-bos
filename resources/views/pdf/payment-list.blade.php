@php
  // $fmt = new NumberFormatter( 'us_US', NumberFormatter::CURRENCY );
  $extraWorksCollection = collect();
  $grandTotal = 0;
 //dd('Generating payment list for order: ' . $order->orderColors);
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
      <h1>PAYMENT LIST {{ strtoupper(optional($order->typeOfWork)->name ?? $order->service) }}</h1>
      <div id="company" class="clearfix">
        <div><span>DATE</span> {{ Carbon\Carbon::parse($order->installation_date)->format('m/d/Y') }}</div>
      </div>
      <div id="project">
        <div><span>ORDER NAME</span> {{ $order->name }}</div>
        <div><span>CLIENT PHONE</span> {{!empty($order->client->phone) ? ', ' . $order->client->phone : ''  }}</div>
        <div><span>ADDRESS</span>{{ 
                        ($order->job_address ?? '') .
                        (!empty($order->city) ? ', ' . $order->city : '') .
                        (!empty($order->job_state) ? ', ' . $order->job_state : '') .
                        (!empty($order->job_zip) ? ', ' . $order->job_zip : '') 
                    }}<div>
        <div><span>COLOR</span> {{ $order->orderColors->pluck('name')->implode(', ') }}</div>
      </div>
    </header>
      <main>
        <table class='info-table'>
          <thead>
            <tr>
              <th class="service">PRODUCT</th>
              <th class="desc">CATEGORY</th>
              <th class="desc">TYPE OF WORK</th>
              <th class="number-headers">QTY</th>
              <th class="number-headers">PRICE</th>
              <th class="number-headers">SUBTOTAL</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($order->orderProducts->groupBy('productCategory.name') as $key => $products)
              <tr>
                <td class="table-section" colspan="6">{{ $key }}</td>
              </tr>
              @foreach ($products as $product)
                @php
                  $extraWorksCollection = $extraWorksCollection->merge($product->orderProductExtraWorks);
                  $grandTotal += $product->total_price;
                  $storefrontBasePrice = null;
                  $parsedNewStorefrontPrice = floatval($product->new_price_storefront ?? 0);
                  if ((int) $product->type_of_product_id === 3) {
                    $productCosts = $product->productConfig?->productCosts ?? collect();
                    $storefrontBasePrice = optional(
                      $productCosts->firstWhere('type_of_work_id', $product->type_of_work_id)
                    )->price;
                  }
                @endphp
                <tr>
                  <td class="service">&nbsp;</td>
                  <td class="desc">
                    {{ optional($product->productConfig)->name }}
                    @if ($product->installation_other_level) 
                       (Other Level) 
                    @endif
                  </td>
                  <td >{{ optional($product->typeOfWork)->name }}</td>
                  <td class="qty">
                    {{ $product->qty }}
                    @if ((int) $product->type_of_product_id === 3)
                      ({{ $product->storefront_area }} SQFT)
                    @endif
                  </td>
                  <td class="unit">
                    @if ((int) $product->type_of_product_id === 3)
                      @if ($parsedNewStorefrontPrice !== 0.0)
                        {{ '$' . number_format($parsedNewStorefrontPrice, 2, '.', ',') }}
                      @elseif (!is_null($storefrontBasePrice))
                        {{ ' $' . number_format($storefrontBasePrice, 2, '.', ',') }}
                      @else
                        {{ ' N/A' }}
                      @endif
                    @else
                      {{ '$' . number_format($product->unit_price, 2, '.', ',') }}
                    @endif
                  </td>
                  <td class="total">{{ '$' . number_format($product->total_price, 2, '.', ',') /* $fmt->formatCurrency($product->total_price, 'USD')*/ }}</td>
                </tr>
              @endforeach
            @endforeach
            <tr>
              <td class="table-section" colspan="6">Extra Works</td>
            </tr>
           
            @foreach ($extraWorksCollection->groupBy('name') as $key => $extraWork)
              @php 
                $count = $extraWork->sum('pivot.amount');
                $price = $extraWork->first()->price;
                $total = $price  * $count;
                $grandTotal += $total;
              @endphp
              <tr>
                <td class="service" colspan="3">{{ $key }}</td>
                <td class="qty">{{ $count }}</td>
                <td class="unit">{{ '$' . number_format($price, 2, '.', ',') /* $fmt->formatCurrency($price, 'USD')*/ }}</td>
                <td class="total">{{ '$' . number_format($total, 2, '.', ',')/* $fmt->formatCurrency($total, 'USD')*/ }}</td>
              </tr>
            @endforeach
            <tr>
            @php 
              $travelCost = $order->travelCost->price;
            @endphp
              @if ($order->is_new_travel_cost) 
                    @php 
                    $travelCost = $order->new_travel_cost;
                    @endphp
              @endif
              <td class="service" colspan="5">Travel Cost</td>
              <td class="total">{{ '$' . number_format($travelCost, 2, '.', ',') /* $fmt->formatCurrency($order->travelCost->price, 'USD' )*/ }}</td>
            </tr>
            <tr>
              <td class="service" colspan="5">Other Cost</td>
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
              <td class='order-notes' colspan='3'>
                <table class='summary-table'>
                  <tr>
                    <td colspan='2' class="total border-right">Total</td>
                    <td class="total">{{ '$' . number_format($grandTotal + $travelCost + $order->additional_travel_costs, 2, '.', ',') /* $fmt->formatCurrency($grandTotal + $order->travelCost->price + $order->additional_travel_costs, 'USD' ) */ }}</td>
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
                        {{ '$' . number_format(($grandTotal + $travelCost + $order->additional_travel_costs) * $order->initial_payment_percentage / 100, 2, '.', ',') }}
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
                        {{ '$' . number_format(($grandTotal + $travelCost + $order->additional_travel_costs) * (100 - $order->initial_payment_percentage) / 100, 2, '.', ',') }}
                      @endif
                    </td>
                  </tr>
                  <tr>
                    <td colspan='2' class="grand total border-right">Total</td>
                    <td class="grand total">{{ '$' . number_format($grandTotal + $travelCost + $order->additional_travel_costs, 2, '.', ',') /* $fmt->formatCurrency($grandTotal + $order->travelCost->price + $order->additional_travel_costs, 'USD' ) */ }}</td>
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
