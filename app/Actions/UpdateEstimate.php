<?php
namespace App\Actions;

use App\Enum\GlassTypeEnum;
use App\Enum\ProductSystemEnum;
use App\Enum\RoleEnum;
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
        $this->updateProductPricesOnMarkupChange($estimate, $request->markup);
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
        'tax_rate' => $request->tax_rate,
        'installation' => $request->installation,
        'permit' => $request->permit,
        'other' => $request->other,
        'external_purchase_id' => $request->external_purchase_id,
      ];

      if (auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$ACCOUNT_MANAGER)) {
          $orderData['rg_other_price'] = $request->rg_other_price;
          $orderData['order_promotion'] = $request->order_promotion;
      }

      if (auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$ACCOUNT_MANAGER) || auth()->user()->hasRole(RoleEnum::$DEALER)) {
        $estimateValues['subdealer_other'] = $request->subdealer_other;
      }

      $estimate->update($orderData);
    });
  }

  public function updateProductsPricesOnGlassTypeChanged($estimate, $glassType) {
    $estimate->products()->each(function($product) use ($glassType, $estimate) {
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
          $newGlassType = $glassType == GlassTypeEnum::$RUSH_GLASS_TYPE ? $glass->getRushGlass() : $glass->getGlass316();
          $cuttingListObject = new FixedWindowsProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $newGlassType
          );

          $unitPrice = $cuttingListObject->getUnitPrice();
          break;
        case ProductSystemEnum::$HORIZONTAL_ROLLER:
          $newGlassType = $glassType == GlassTypeEnum::$RUSH_GLASS_TYPE ? $glass->getRushGlass() : $glass->getGlass316();
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
          $newGlassType = $glassType == GlassTypeEnum::$RUSH_GLASS_TYPE ? $glass->getRushGlass() : $glass->getGlass18();
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

      $unitPrice = $unitPrice;
      $totalPrice = $unitPrice * $product->qty;
      $dealerUnitPrice = $unitPrice + ($unitPrice * $estimate->company_markup / 100);
      $dealerTotalPrice = $dealerUnitPrice * $product->qty;
      $subdealerUnitPrice = $dealerUnitPrice + ($dealerUnitPrice * $estimate->user_markup / 100);
      $subdealerTotalPrice = $subdealerUnitPrice * $product->qty;
      $customerUnitPrice = $subdealerUnitPrice + ($subdealerUnitPrice * $product->markup / 100);
      $customerTotalPrice = $customerUnitPrice * $product->qty;
      $dealerPromotionDiscount = $dealerUnitPrice * $estimate->company_promotion / 100;
      $dealerPromotionTotalDiscount = $dealerPromotionDiscount * $product->qty;

      $product->update([
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
        'glass_type' => $newGlassType
      ]);
    });
  }

  public function updateProductPricesOnMarkupChange($estimate, $markup) {
    $estimate->products()->each(function($product) use ($markup, $estimate) {
      $unitPrice = $product->unit_price;
      $totalPrice = $unitPrice * $product->qty;
      $markup_to_apply = $estimate->company_markup;
      if ($product->system != ProductSystemEnum::$FIXED_WINDOWS && $product->system != ProductSystemEnum::$HORIZONTAL_ROLLER && $product->system != ProductSystemEnum::$SINGLE_HUNG) {
        $markup_to_apply = $estimate->external_products_markup;
      }
      $dealerUnitPrice = $unitPrice + ($unitPrice * $markup_to_apply / 100);
      $dealerTotalPrice = $dealerUnitPrice * $product->qty;
      $subdealerUnitPrice = $dealerUnitPrice + ($dealerUnitPrice * $estimate->user_markup / 100);
      $subdealerTotalPrice = $subdealerUnitPrice * $product->qty;
      $customerUnitPrice = $subdealerUnitPrice + ($subdealerUnitPrice * $markup / 100);
      $customerTotalPrice = $customerUnitPrice * $product->qty;
      $dealerPromotionDiscount = $dealerUnitPrice * $estimate->company_promotion / 100;
      $dealerPromotionTotalDiscount = $dealerPromotionDiscount * $product->qty;

      $product->update([
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
        'markup' => $markup
      ]);
    });
  }
}
