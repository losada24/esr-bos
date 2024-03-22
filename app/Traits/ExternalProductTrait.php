<?php

namespace App\Traits;


trait ExternalProductTrait {

  public function getExtraMullionFields($externalProducts) {
      $result = [];

      foreach ($externalProducts as $externalProduct) {
          $index = array_search($externalProduct->extras['configuration'], array_column($result, 'configuration'));
          if ($index != '') {
            $result[$index]['height'] = $externalProduct['height'];
          } else {
            $result[] = [
              'configuration' => $externalProduct->extras['configuration'],
              'height' => $externalProduct['height'],
              'width' => $externalProduct['width']
            ];
          }
      }
      return $result;
  }
}
