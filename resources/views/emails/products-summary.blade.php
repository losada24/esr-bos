@inject('fractions', 'App\Helpers\FractionsHelpers')
@inject('prices', 'App\Helpers\PricesHelpers')

<table>
  <thead>
      <tr>
          <th>System</th>
          <th>Mark</th>
          <th>Qty</th>
          <th>Size</th>
          <th>Frame Color</th>
          <th>Glass</th>
          <th>Price</th>
          <th>Amount</th>
      </tr>
  </thead>
  <tbody>
      @foreach($order->products as $product)
          <tr>
              <td>{{ $product->system }}</td>
              <td>{{ $product->line_item_name }}</td>
              <td>{{ $product->qty }}</td>
              <td>{{ $fractions->getNumberWithFraction($product->width) }} x {{ $fractions->getNumberWithFraction($product->height) }}</td>
              <td>{{ $product->frame_color }}</td>
              <td>{{ $product->glass_type }}</td>
              <td>{{ $prices->formatPrice($prices->getUnitPriceByRole($product, $role)) }}</td>
              <td>{{ $prices->formatPrice($prices->getTotalPriceByRole($product, $role))}}</td>
          </tr>
      @endforeach
        <tr>
          <td colspan="6"></td>
          <td><strong>Sub Total</strong></td>
          <td>{{ $prices->formatPrice($prices->getSubtotalByRole($order->products, $role))}}</td>
        </tr>
        @if ($prices->getDealerPromotion($order->products, $role) > 0) 
          <tr>
            <td colspan="6"></td>
            <td><strong>Dealer Promotion</strong></td>
            <td>{{ $prices->formatPrice($prices->getDealerPromotion($order->products, $role))}}</td>
          </tr>
        @endif
        @if ($order->order_promotion > 0) 
          <tr>
            <td colspan="6"></td>
            <td><strong>Order Promotion</strong></td>
            <td>{{ $prices->formatPrice($order->order_promotion)}}</td>
          </tr>
        @endif
        @if ($order->rg_other_price > 0) 
          <tr>
            <td colspan="6"></td>
            <td><strong>RG Other Price</strong></td>
            <td>{{ $prices->formatPrice($order->rg_other_price)}}</td>
          </tr>
        @endif
        <tr>
          <td colspan="6"></td>
          <td><strong>Grand Total</strong></td>
          <td>{{ $prices->formatPrice($prices->getGrandTotalByRole($order, $role))}}</td>
        </tr>
  </tbody>
</table>