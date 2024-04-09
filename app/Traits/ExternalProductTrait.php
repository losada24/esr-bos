<?php

namespace App\Traits;

use App\Enum\ExternalProductEnum;
use App\Models\ExternalProductConfiguration;

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

  public function getCasementOpeningOptions() {
    $result = [
      'RIGHT OPENING (XR)',
      'LEFT OPENING (XR)'
    ];
    
    return $result;
    /* $casementsProducts = ExternalProductConfiguration::where('external_product', ExternalProductEnum::$CASEMENT)->get();

    foreach ($casementsProducts as $casementProduct) {
      if (isset($casementProduct->extras['opening']) && !isset($result[$casementProduct->extras['opening']])) {
        $result[$casementProduct->extras['opening']] = $casementProduct->extras['opening'];
      }
    }

    return $result; */
  }
}
