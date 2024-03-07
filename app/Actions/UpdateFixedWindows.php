<?php
namespace App\Actions;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Products\FixedWindowsProduct;
use App\Enum\ProductSystemEnum;
use App\Models\Order;

class UpdateFixedWindows {

  public function handle(Request $request, Product $product) {

    DB::transaction(function() use ($request, $product) {

      if( !$product )
      {
          throw new \Exception('Product not updated');
      }

      $fixedWindowsProduct = new FixedWindowsProduct(
        $request->width,
        $request->height,
        $request->frame_color,
        $request->glass_type,
        $request->muntin_panels,
        $request->panel_a,
        $request->muntin_pattern,
        $request->muntin_interior_style,
        $request->muntin_exterior_style,
        $request->vertical_lines,
        $request->horizontal_lines
      );

      $estimate = Order::find($request->order_id);
      $unitPrice = $fixedWindowsProduct->getUnitPrice();
      $totalPrice = $unitPrice * $request->qty;
      $dealerUnitPrice = $unitPrice + ($unitPrice * $estimate->company_markup / 100);
      $dealerTotalPrice = $dealerUnitPrice * $request->qty;
      $subdealerUnitPrice = $dealerUnitPrice + ($dealerUnitPrice * $estimate->user_markup / 100);
      $subdealerTotalPrice = $subdealerUnitPrice * $request->qty;
      $customerUnitPrice = $subdealerUnitPrice + ($subdealerUnitPrice * $request->markup / 100);
      $customerTotalPrice = $customerUnitPrice * $request->qty;
      $dealer_promotion_discount = $dealerUnitPrice * $estimate->company_promotion / 100;
      $dealer_promotion_total_discount = $dealer_promotion_discount * $request->qty;

      $productData = [
        'order_id' => $request->order_id,
        'system' => ProductSystemEnum::$FIXED_WINDOWS,
        'width' => $request->width,
        'height' => $request->height,
        'line_item_name' => $request->mark,
        'qty' => $request->qty,
        'markup' => $request->markup,
        'frame_color' => $request->frame_color,
        'glass_type' => $request->glass_type,
        'glass_color' => $request->glass_color,
        'low_e' => $request->low_e,
        'privacy' => $request->privacy,
        'unit_price' => $unitPrice,
        'total_price' => $totalPrice,
        'dealer_unit_price' => $dealerUnitPrice,
        'dealer_total_price' => $dealerTotalPrice,
        'sub_dealer_unit_price' => $subdealerUnitPrice,
        'sub_dealer_total_price' => $subdealerTotalPrice,
        'customer_unit_price' => $customerUnitPrice,
        'customer_total_price' => $customerTotalPrice,
        'dealer_promotion_discount' => $dealer_promotion_discount,
        'dealer_promotion_total_discount' => $dealer_promotion_total_discount,
        'user_id' => auth()->user()->id,
        'extras' => [
          'config' => 'O',
          'muntin_panels' => $request->muntin_panels,
          'panel_a' => $request->panel_a,
          'muntin_pattern' => $request->muntin_pattern,
          'muntin_interior_style' => $request->muntin_interior_style,
          'muntin_exterior_style' => $request->muntin_exterior_style,
          'horizontal_lines' => $request->horizontal_lines,
          'vertical_lines' => $request->vertical_lines
        ],
      ];

      $product->update($productData);
    });
  }
}
