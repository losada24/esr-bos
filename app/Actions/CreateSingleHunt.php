<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Enum\ProductSystemEnum;
use App\Events\OrderCreated;
use App\Products\FixedWindowsProduct;

class CreateSingleHunt {

  public function handle(Request $request) {
    DB::transaction(function() use ($request) {
      $fixedWindowsProduct = new FixedWindowsProduct(
        $request->width,
        $request->height
      );

      $unitPrice = $fixedWindowsProduct->getUnitPrice();
      $markupValue = $unitPrice * ($request->markup / 100);
      $unitPriceWithMarkup = $unitPrice + $markupValue;

      $product = Product::create([
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
      ]);
      
      if( !$product )
      {
          throw new \Exception('Fixed Windows not created');
      }
    });
  }
}
