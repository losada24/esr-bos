<?php

namespace App\Traits;

use App\Enum\ProductSystemEnum;
use App\Products\FixedWindowsProduct;
use App\Products\HorizontalRollerProduct;
use App\Products\SingleHuntProduct;

trait Product {

  public function getCompanyMockup(): int {
    $companyMockup = auth()->user()->company->mockup;
    return $companyMockup ?? 0 ;
  }

  public function getCompanyPromotion(): int {
    $companyMockup = auth()->user()->company->promotion;
    return $companyMockup ?? 0 ;
  }

  public function getMaterialColor($frame_color) {
    $result = 'W';
    if ($frame_color == 'BRONZE') {
      $result = 'BR';
    }

    return $result;
  }

  public function getCuttingListObject($part, $material, $qty, $size = 0) {
    $cuttingListObject = new \stdClass();
    $cuttingListObject->part = $part;
    $cuttingListObject->material = $material;
    $cuttingListObject->qty = $qty;
    $cuttingListObject->size = $size;
    return $cuttingListObject;
  }

  public function updateMaterialsConsumption($materialConsumptionForProduct, &$materialConsumption) {
    collect($materialConsumptionForProduct)->each(function($value, $key) use (&$materialConsumption) {
      $index = array_search($key, array_column($materialConsumption, 'name'));
      if( $index !== false ) {
        $materialConsumption[$index]['amount'] += $value['amount'];
      } else {
        $materialConsumption[] = [
          'name' => $key,
          'amount' => $value['amount'],
          'unit_of_measurement' => $value['unit_of_measurement'],
          'storage_measure' => $value['storage_measure'],
        ];
      }
    });
  }

  public function getMaterialConsumption($order) {
      $materialConsumption = [];
      $order->products->each(function($product) use (&$materialConsumption) {
        switch($product->system) {
          case ProductSystemEnum::$FIXED_WINDOWS:
            $cuttingListObject = new FixedWindowsProduct(
              $product->width,
              $product->height,
              $product->frame_color,
              $product->glass_type
            );

            $materialConsuptionForProduct = $cuttingListObject->getMaterialConsumption($product->qty);
            $this->updateMaterialsConsumption($materialConsuptionForProduct, $materialConsumption);
            break;
          case ProductSystemEnum::$HORIZONTAL_ROLLER:
            $cuttingListObject = new HorizontalRollerProduct(
              $product->width,
              $product->height,
              $product->frame_color,
              $product->glass_type,
              $product->extras['screen']
            );

            $materialConsuptionForProduct = $cuttingListObject->getMaterialConsumption($product->qty);
            $this->updateMaterialsConsumption($materialConsuptionForProduct, $materialConsumption);
            break;
          case ProductSystemEnum::$SINGLE_HUNG:
            $cuttingListObject = new SingleHuntProduct(
              $product->width,
              $product->height,
              $product->frame_color,
              $product->glass_type,
              $product->extras['screen']
            );

            $materialConsuptionForProduct = $cuttingListObject->getMaterialConsumption($product->qty);
            $this->updateMaterialsConsumption($materialConsuptionForProduct, $materialConsumption);
            break;
        }
      });

      return $materialConsumption;
  }

  public function getCuttingList($order) {
    return $order->products->map(function($product, $key) {
      $cuttingList = [];
      switch($product->system) {
        case ProductSystemEnum::$FIXED_WINDOWS:
          $cuttingListObject = new FixedWindowsProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $product->glass_type
          );
          $cuttingList = $cuttingListObject->getCuttingList($product->qty);
          break;
        case ProductSystemEnum::$HORIZONTAL_ROLLER:
          $cuttingListObject = new HorizontalRollerProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $product->glass_type,
            $product->extras['screen']
          );
          $cuttingList = $cuttingListObject->getCuttingList($product->qty);
          break;
        case ProductSystemEnum::$SINGLE_HUNG:
          $cuttingListObject = new SingleHuntProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $product->glass_type,
            $product->extras['screen']
          );
          $cuttingList = $cuttingListObject->getCuttingList($product->qty);
          break;
      }

      return [
        'id' => $product->id,
        'visual_id' => $key,
        'line_item_name' => $product->line_item_name,
        'system' => $product->system,
        'qty' => $product->qty,
        'width' => $product->width,
        'height' => $product->height,
        'cutting_list' => $cuttingList,
        'frame_color' => $product->frame_color,
        'extras' => $product->extras,
      ];
    });
  }
}
