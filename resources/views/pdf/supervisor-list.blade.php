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
      <h1>MATERIAL LIST {{ strtoupper($order->typeOfWork->name) }}</h1>
      <div id="company" class="clearfix">
        <div><span>DATE</span> {{ Carbon\Carbon::parse($order->installation_date)->format('m/d/Y') }}</div>
      </div>
      <div id="project">
        <div><span>CONTACT</span> {{ $order->client->name }}</div>
        <div><span>ADDRESS</span> {{ $order->job_address}}</div>
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
                </tr>
              @endforeach
            @endforeach
            <tr>
              <td class="table-section" colspan="5">Extra Works</td>
            </tr>
           
            @foreach ($extraWorksCollection->groupBy('name') as $key => $extraWork)
              @php 
                $count = $extraWork->sum('pivot.amount');
                
              @endphp
              <tr>
                <td class="service" colspan="2">{{ $key }}</td>
                <td class="qty">{{ $count }}</td>
                
              </tr>
            @endforeach
            <tr>
              <td class='order-notes' colspan="3">
                @if ($order->notes != '')
                  <div class="notes">
                    <strong>Notes:</strong>
                    <p>{{ $order->notes }}</p>
                  </div>
                @endif
              </td>
            </tr>
          </tbody>
        </table>
        
      </main>
      <footer>
        
      </footer>
  </body>
</html>
