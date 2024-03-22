<?php

namespace App\Products;

use App\Enum\ExternalProductEnum;
use App\Interfaces\IProduct;
use App\Models\ExternalProductConfiguration;
use App\Traits\Product;
use App\Traits\Fractions;

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