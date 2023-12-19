<?php

namespace App\Traits;

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
}
