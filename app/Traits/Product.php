<?php

namespace App\Traits;

use App\Enum\ExternalProductEnum;
use App\Enum\ProductSystemEnum;
use App\Products\CasementProduct;
use App\Products\FixedWindowsProduct;
use App\Products\HorizontalRollerProduct;
use App\Products\MullionProduct;
use App\Products\SingleHuntProduct;

trait Product {

  public function getCompanyMockup(): int {
    $companyMarkup = auth()->user()->company->markup;
    return $companyMarkup ?? 0;
  }

  public function getCompanyPromotion(): int {
    $companyPromotion = auth()->user()->company->promotion;
    return $companyPromotion ?? 0;
  }

  public function getMaterialColor($frame_color) {
    $result = 'W';
    if ($frame_color == 'BRONZE') {
      $result = 'BR';
    }

    return $result;
  }

  public function getGlassSize($size) {
    $result = $size;
    if (is_numeric($size) && floor($size) != $size) {
      $result = ceil($size);
    }

    if ($result % 2 != 0) {
      $result++;
    }

    return (integer)$result;
  }

  public function getCuttingListObject($part, $material, $qty, $size = 0, $rawSize = 0) {
    $cuttingListObject = new \stdClass();
    $cuttingListObject->part = $part;
    $cuttingListObject->material = $material;
    $cuttingListObject->qty = $qty;
    $cuttingListObject->size = $size;
    $cuttingListObject->rawSize = $rawSize;
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
          'notes' => $value['notes'],
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

  public function getMaterialRelease($order) {
      $materialRelease = [];
      $order->products->each(function($product) use (&$materialRelease) {
        switch($product->system) {
          case ProductSystemEnum::$FIXED_WINDOWS:
            $cuttingListObject = new FixedWindowsProduct(
              $product->width,
              $product->height,
              $product->frame_color,
              $product->glass_type
            );

            $materialReleaseForProduct = $cuttingListObject->getMaterialRelease($product->qty);
            $this->updateMaterialsConsumption($materialReleaseForProduct, $materialRelease);
            break;
          case ProductSystemEnum::$HORIZONTAL_ROLLER:
            $cuttingListObject = new HorizontalRollerProduct(
              $product->width,
              $product->height,
              $product->frame_color,
              $product->glass_type,
              $product->extras['screen']
            );

            $materialReleaseForProduct = $cuttingListObject->getMaterialRelease($product->qty);
            $this->updateMaterialsConsumption($materialReleaseForProduct, $materialRelease);
            break;
          case ProductSystemEnum::$SINGLE_HUNG:
            $cuttingListObject = new SingleHuntProduct(
              $product->width,
              $product->height,
              $product->frame_color,
              $product->glass_type,
              $product->extras['screen']
            );

            $materialReleaseForProduct = $cuttingListObject->getMaterialRelease($product->qty);
            $this->updateMaterialsConsumption($materialReleaseForProduct, $materialRelease);
            break;
          case ExternalProductEnum::$MULLION:
            $cuttingListObject = new MullionProduct(
              $product->width,
              $product->height,
              $product->frame_color,
              $product->extras['config']
            );

            $materialReleaseForProduct = $cuttingListObject->getMaterialRelease($product->qty);
            $this->updateMaterialsConsumption($materialReleaseForProduct, $materialRelease);
            break;      
          }
      });

      return $materialRelease;
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

  public function createCuttingListItem($product, $cuttingListForProduct) {
    return [
      'part' => $product->part,
      'qty' => $product->qty,
      'size' => $product->size,
      'rawSize' => $product->rawSize,
      'visual_id' => $cuttingListForProduct['visual_id'],
      'line_item_name' => $cuttingListForProduct['line_item_name'],
      'system' => $cuttingListForProduct['system'],
      'width' => $cuttingListForProduct['width'],
      'height' => $cuttingListForProduct['height'],
      'frame_color' => $cuttingListForProduct['frame_color'],
      'extras' => $cuttingListForProduct['extras'],
    ];
  }

  public function orderedCuttingList($order) {
    $cuttingList = $this->getCuttingList($order);
    $orderedCuttingList = [
      [
        'material' => 'VW 111 W',
        'items' => []
      ],
      [
        'material' => 'VW 111 BR',
        'items' => []
      ],
      [
        'material' => 'VW 103 W',
        'items' => []
      ],
      [
        'material' => 'VW 103 BR',
        'items' => []
      ],
      [
        'material' => 'VW 112 W',
        'items' => []
      ],
      [
        'material' => 'VW 112 BR',
        'items' => []
      ]
    ];

    collect($cuttingList)->each(function($cuttingListForProduct) use (&$orderedCuttingList) {
      collect($cuttingListForProduct['cutting_list'])->each(function($productItem) use (&$orderedCuttingList, $cuttingListForProduct) {
        if ($productItem->rawSize != 0 && ($productItem->material != 'SS 0001 W' && $productItem->material != 'SS 0001 BR')) {
          $index = array_search($productItem->material, array_column($orderedCuttingList, 'material'));
          if ($index !== false) {
            $orderedCuttingList[$index]['items'][] = $this->createCuttingListItem($productItem, $cuttingListForProduct);
            $orderedCuttingList[$index]['items'] = collect($orderedCuttingList[$index]['items'])->sortByDesc('rawSize')->values()->all();
          } else {
            $orderedCuttingList[] = [
              'material' => $productItem->material,
              'items' => [
                $this->createCuttingListItem($productItem, $cuttingListForProduct)
              ]
            ];
          }
        }
      });
    });

    $result = array_filter($orderedCuttingList, function ($material) {
      return count($material['items']) > 0;
    }, ARRAY_FILTER_USE_BOTH);

    return array_values($result);
  }

  public function getPOGlass($order) {
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
          $cuttingList = $cuttingListObject->getPoGlass($product->qty);
          break;
        case ProductSystemEnum::$HORIZONTAL_ROLLER:
          $cuttingListObject = new HorizontalRollerProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $product->glass_type,
            $product->extras['screen']
          );
          $cuttingList = $cuttingListObject->getPoGlass($product->qty);
          break;
        case ProductSystemEnum::$SINGLE_HUNG:
          $cuttingListObject = new SingleHuntProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $product->glass_type,
            $product->extras['screen']
          );
          $cuttingList = $cuttingListObject->getPoGlass($product->qty);
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

  public function getPOScreen($order) {
    return $order->products->map(function($product, $key) {
      $cuttingList = [];
      switch($product->system) {
        case ProductSystemEnum::$HORIZONTAL_ROLLER:
          $cuttingListObject = new HorizontalRollerProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $product->glass_type,
            $product->extras['screen']
          );
          $cuttingList = $cuttingListObject->getPoScreen($product->qty);
          break;
        case ProductSystemEnum::$SINGLE_HUNG:
          $cuttingListObject = new SingleHuntProduct(
            $product->width,
            $product->height,
            $product->frame_color,
            $product->glass_type,
            $product->extras['screen']
          );
          $cuttingList = $cuttingListObject->getPoScreen($product->qty);
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

  public function getBalancePO($order) {
    return $order->products->map(function($product) {
      $singleHomeObject = new SingleHuntProduct(
        $product->width,
        $product->height,
        $product->frame_color,
        $product->glass_type,
        $product->extras['screen']
      );

      $balanceInfo = $singleHomeObject->getBalancesBySize();

      return [
        'id' => $product->id,
        'qty' => $product->qty,
        'size' => $balanceInfo[2],
        'part_no' => $balanceInfo[3],
      ];
    });
  }
}
