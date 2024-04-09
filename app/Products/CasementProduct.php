<?php

namespace App\Products;

use App\Enum\ExternalProductEnum;
use App\Interfaces\IProduct;
use App\Models\ExternalProductConfiguration;
use App\Traits\Product;
use App\Traits\Fractions;

class CasementProduct implements IProduct {

    use Product, Fractions;
    public $width;
    public $height;
    public $frameColor;
    public $config;
    public $glass_type;
    public $muntinPanels;
    public $panelA;
    public $panelB;
    public $muntinPattern;
    public $muntinInteriorStyle;
    public $muntinExteriorStyle;
    public $verticalLines;
    public $horizontalLines;
    public $screenRequired;
    
    public function __construct(
      $width, 
      $height, 
      $frameColor,
      $config,
      $glass_type,
      $screenRequired,
      $muntinPanels = false,
      $panelA = false,
      $panelB = false,
      $muntinPattern = "",
      $muntinInteriorStyle = "",
      $muntinExteriorStyle = "",
      $verticalLines = 0,
      $horizontalLines = 0
    ) {

        $this->frameColor = $frameColor;
        $this->width = $width;
        $this->height = $height;
        $this->config = $config;
        $this->glass_type = $glass_type;
        $this->screenRequired = $screenRequired;
        $this->muntinPanels = $muntinPanels;
        $this->panelA = $panelA;
        $this->panelB = $panelB;
        $this->muntinPattern = $muntinPattern;
        $this->muntinInteriorStyle = $muntinInteriorStyle;
        $this->muntinExteriorStyle = $muntinExteriorStyle;
        $this->verticalLines = $verticalLines;
        $this->horizontalLines = $horizontalLines;
    }

    public function getScreenWidth() {
      return round($this->width, 3);
    }

    public function getScreenHeigth() {
      return round($this->height, 3);
    }
    
    public function getUnitPrice() {
        $unitPriceCost = 0;
        $casementProducts = ExternalProductConfiguration::where('external_product', ExternalProductEnum::$CASEMENT)
          ->where('extras->configuration', $this->config)
          ->where('extras->glass_type', $this->glass_type)
          ->orderBy('width', 'desc')
          ->orderBy('height', 'desc')
          ->get();

        foreach ($casementProducts as $casementProduct) {
          if ($this->width > $casementProduct->width) {
            break;
          }
          $lastWidth = $casementProduct->width;
        }

        //dd($casementProducts);

        $casementProducts->where('width', $lastWidth)->each(function ($product) use (&$unitPriceCost) {
          if ($this->height > $product->height) {
            return;
          }
          $unitPriceCost = $product->price;
        });
        
        $screenCost = 0;
        $screen_price_by_sqft = config('custom.screen_price_by_sqft');
        if ($this->screenRequired) {
          $screenCost = $this->getScreenWidth() * $this->getScreenHeigth() / 144 * $screen_price_by_sqft;
        }

        //GET MUNTIN COST
        $muntinPriceBySqft = config('custom.muntin_price_by_sqft');
        $muntinCost = 0;
        if ($this->muntinPanels) {
          $horizontalMuntinsCost = ($this->horizontalLines - 1) * $this->width * $muntinPriceBySqft * 0.083;
          $verticalMuntingCost = ($this->verticalLines - 1) * $this->height * $muntinPriceBySqft * 0.083;

          if (!empty($this->muntinInteriorStyle)) {
            $muntinCost = $horizontalMuntinsCost + $verticalMuntingCost;
          }
          if(!empty($this->muntinExteriorStyle)) {
            $muntinCost += $horizontalMuntinsCost + $verticalMuntingCost;
          }

          if ($this->panelA == true && $this->panelB == true) {
            $muntinCost *= 2;
          }
        }
        
        return round($unitPriceCost + $muntinCost + $screenCost, 2);
    }
}