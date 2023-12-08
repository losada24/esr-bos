<?php
namespace App\Actions;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Products\FixedWindowsProduct;
use App\Enum\ProductSystemEnum;

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
        $request->glass_type
      );

      $unitPrice = $fixedWindowsProduct->getUnitPrice();
      $markupValue = $unitPrice * ($request->markup / 100);
      $unitPriceWithMarkup = $unitPrice + $markupValue;

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
        'unit_price' => $unitPriceWithMarkup,
        'total_price' => $unitPriceWithMarkup * $request->qty,
        'user_id' => auth()->user()->id,
      ];

      $product->update($productData);
    });
  }
}
