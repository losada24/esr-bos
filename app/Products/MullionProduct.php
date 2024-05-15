<?php

namespace App\Products;

use App\Enum\ExternalProductEnum;
use App\Enum\UnitOfMeasurement;
use App\Interfaces\IProduct;
use App\Models\ExternalProductConfiguration;
use App\Traits\Product;
use App\Traits\Fractions;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;

class MullionProduct implements IProduct {

    use Product, Fractions;
    public $width;
    public $height;
    public $frameColor;
    public $config;
    
    public function __construct(
      $width, 
      $height, 
      $frameColor,
      $config
    ) {

        $this->frameColor = $frameColor;
        $this->width = $width;
        $this->height = $height;
        $this->config = $config;
    }

    public function getMaterialRelease($qty) {
      $materials['Mullion(' . $this->getNumberWithFraction($this->width) . ' x ' . $this->getNumberWithFraction($this->height) . ')'] = [
        'amount' => $qty,
        'unit_of_measurement' => UnitOfMeasurement::$UNIT_OF_MEASUREMENT["UNIT"],
        'storage_measure' => UnitOfMeasurement::$UNIT_OF_MEASUREMENT["UNIT"],
        'notes' => 'Color: ' . $this->frameColor, 
      ];
      
      return $materials;
    }

    
    public function getUnitPrice() {
        $unitPriceCost = 0;
        $mullionProducts = ExternalProductConfiguration::where('external_product', ExternalProductEnum::$MULLION)
          ->where('extras->configuration', $this->config)
          ->orderBy('height', 'desc')
          ->get();

        foreach ($mullionProducts as $mullionProduct) {
          if ($this->height > $mullionProduct->height) {
            break;
          }
          $unitPriceCost = $mullionProduct->price;
        }
        
        return round($unitPriceCost, 2);
    }
}