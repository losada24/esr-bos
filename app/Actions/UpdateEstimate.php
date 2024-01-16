<?php
namespace App\Actions;

use App\Enum\ProductSystemEnum;
use App\Models\Order;
use App\Products\FixedWindowsProduct;
use App\Products\Glass;
use App\Products\HorizontalRollerProduct;
use App\Products\SingleHuntProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateEstimate {

  public function handle(Request $request, Order $estimate) {

    DB::transaction(function() use ($request, $estimate) {

      if( !$estimate )
      {
          throw new \Exception('Not not updated');
      }

      if ($estimate->markup != $request->markup) {
        $estimate->products()->update([
          'markup' => $request->markup
        ]);
      }

      if ($estimate->glass_type != $request->glass_type) {
        $this->updateProductsPricesOnGlassTypeChanged($estimate, $request->glass_type);
      }
      
      $orderData = [
        'name' => $request->name,
        'project_name' => $request->project_name,
        'client_id' => $request->client_id,
        'frame_color' => $request->frame_color,
        'glass_color' => $request->glass_color,
        'glass_type' => $request->glass_type,
        'markup' => $request->markup,
        'notes' => $request->notes,
        'user_id' => auth()->user()->id,
        'tax_rate' => $request->tax_rate,
        'installation' => $request->installation,
        'permit' => $request->permit,
        'other' => $request->other,
        'external_purchase_id' => $request->external_purchase_id,
      ];

      $estimate->update($orderData);
    });
  }

  public function updateProductsPricesOnGlassTypeChanged($estimate, $glassType) {
    $estimate->products()->each(function($product) use ($glassType) {
      $unitPrice = 0;
      $newGlassType = "";
      $glass = new Glass(
        $glassType,
        $product->glass_color,
        $product->low_e,
        $product->privacy
      );
      
      switch($product->system) {
        case ProductSystemEnum::$FIXED_WINDOWS:
          $newGlassType = $glass->getGlass316();
          $cuttingListObject = new FixedWindowsProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $newGlassType
          );

          $unitPrice = $cuttingListObject->getUnitPrice();
          break;
        case ProductSystemEnum::$HORIZONTAL_ROLLER:
          $newGlassType = $glass->getGlass316();
          $cuttingListObject = new HorizontalRollerProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $newGlassType,
            $product->extras['screen']
          );

          $unitPrice = $cuttingListObject->getUnitPrice();
          break;
        case ProductSystemEnum::$SINGLE_HUNG:
          $newGlassType = $glass->getGlass18();
          $cuttingListObject = new SingleHuntProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $newGlassType,
            $product->extras['screen']
          );

          $unitPrice = $cuttingListObject->getUnitPrice();
          break;
      }

      $product->update([
        'unit_price' => $unitPrice,
        'total_price' => $unitPrice * $product->qty,
        'glass_type' => $newGlassType
      ]);
    });
  }
}
