<?php

namespace App\Products;

use App\Interfaces\IProduct;
use App\Models\RawMaterial;

class FixedWindowsProduct implements IProduct {

    public $width;
    public $height;
    
    /* 
      public $frame_color;
      public $line_item_name;
      public $glass;
      public $qty;
      public $markup;
    */

    public function __construct($width, $height /* $frame_color, $line_item_name, $glass, $qty, $markup */) {
        $this->width = $width;
        $this->height = $height;
        
        /* $this->frame_color = $frame_color;
        $this->line_item_name = $line_item_name;
        $this->glass = $glass;
        $this->qty = $qty; 
        $this->markup = $markup;*/
         
    }

    public function getGlassHeigth() {
      return $this->height - 1.875;
    }

    public function getGlassWidth() {
      return $this->width - 4.312;
    }

    public function getUnitPrice() {
        $frameHeadMaterial = RawMaterial::where('id', 1)->first(); // MATERIAL 101
        $frameHeadCost = $this->width * 0.83 * $frameHeadMaterial->cost_per_unit * 2;
        $jambMaterial = RawMaterial::where('id', 2)->first(); // MATERIAL 103
        $jambCost = (($this->height - 1.37) * 0.83) * $jambMaterial->cost_per_unit * 2;
        $screwMaterial = RawMaterial::where('id', 4)->first(); // MATERIAL Screws 8x1
        $screwMaterialCost = $screwMaterial->cost_per_unit * 12;
        $glazingBeadMaterial = RawMaterial::where('id', 3)->first(); // MATERIAL 108
        $glazingBeadVerticalCost = ($this->getGlassHeigth() - 0.87) * 0.83 * $glazingBeadMaterial->cost_per_unit * 2;
        $glazingBeadHorizontalCost = $this->getGlassWidth() * 0.83 * $glazingBeadMaterial->cost_per_unit * 2;

        // TODO: ADD GLASS COST
        $unitPriceCost = $frameHeadCost + $jambCost + $screwMaterialCost + $glazingBeadVerticalCost + $glazingBeadHorizontalCost;
        return $unitPriceCost;
    }
}