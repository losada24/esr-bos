<?php
namespace App\Actions;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enum\ProductSystemEnum;
use App\Models\Order;
use App\Products\HorizontalRollerProduct;

class UpdateHorizontalRoller {

  public function handle(Request $request, Product $product) {

    DB::transaction(function() use ($request, $product) {

      if( !$product )
      {
          throw new \Exception('Product not updated');
      }

      $horizontalRollerProduct = new HorizontalRollerProduct(
        $request->width,
        $request->height,
        $request->frame_color,
        $request->glass_type,
        $request->screen
      );

      $estimate = Order::find($request->order_id);
      $unitPrice = $horizontalRollerProduct->getUnitPrice();
      $totalPrice = $unitPrice * $request->qty;
      $dealerUnitPrice = $unitPrice + ($unitPrice * $estimate->company_markup / 100);
      $dealerTotalPrice = $dealerUnitPrice * $request->qty;
      $subdealerUnitPrice = $dealerUnitPrice + ($dealerUnitPrice * $estimate->user_markup / 100);
      $subdealerTotalPrice = $subdealerUnitPrice * $request->qty;
      $customerUnitPrice = $subdealerUnitPrice + ($subdealerUnitPrice * $request->markup / 100);
      $customerTotalPrice = $customerUnitPrice * $request->qty;
      $dealerPromotionDiscount = $dealerUnitPrice * $estimate->company_promotion / 100;
      $dealerPromotionTotalDiscount = $dealerPromotionDiscount * $request->qty;

      $productData = [
        'order_id' => $request->order_id,
        'system' => ProductSystemEnum::$HORIZONTAL_ROLLER,
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
        'dealer_promotion_discount' => $dealerPromotionDiscount,
        'dealer_promotion_total_discount' => $dealerPromotionTotalDiscount,
        'extras' => [
          'screen' => $request->screen,
          'config' => $request->config,
          'handle' => $request->handle,
        ],
        'user_id' => auth()->user()->id,
      ];

      $product->update($productData);
    });
  }
}
