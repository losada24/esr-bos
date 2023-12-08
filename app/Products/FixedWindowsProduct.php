<?php

namespace App\Products;

use App\Interfaces\IProduct;
use App\Models\RawMaterial;
use App\Traits\Product;

class FixedWindowsProduct implements IProduct {

    use Product;
    public $width;
    public $height;
    public $frameColor;
    
    public function __construct($width, $height, $frameColor) {
        $this->width = $width;
        $this->height = $height;
        $this->frameColor = $frameColor;
    }

    public function getGlassHeigth() {
      return $this->height - 1.875;
    }

    public function getGlassWidth() {
      return $this->width - 4.312 - 0.065;
    }

    public function getUnitPrice() {


        $frameHeadMaterial = RawMaterial::where('id', 1)->first(); // MATERIAL 101
        $frameHeadCost = $this->width * 0.83 * $frameHeadMaterial->cost_per_unit * 2;
        $jambMaterial = RawMaterial::where('id', 2)->first(); // MATERIAL 103
        $jambCost = (($this->height - 1.37) * 0.83) * $jambMaterial->cost_per_unit * 2;
        $screwMaterial = RawMaterial::where('id', 4)->first(); // MATERIAL Screws 8x1
        $screwMaterialCost = $screwMaterial->cost_per_unit * 12;
        $glazingBeadMaterial = RawMaterial::where('id', 3)->first(); // MATERIAL 108
        $glazingBeadVerticalCost = ($this->getGlassHeigth() - 0.87 + 0.3775) * 0.83 * $glazingBeadMaterial->cost_per_unit * 2;
        $glazingBeadHorizontalCost = ($this->getGlassWidth() + 0.1875) * 0.83 * $glazingBeadMaterial->cost_per_unit * 2;
        $firstFrameColorLetter = substr($this->frameColor, 0, 1);
        $screwCoverMaterial = RawMaterial::where('name', 'SC 001 ' . $firstFrameColorLetter)->first(); // SC 001 (W or B)
        $screwCoverCost =  $this->getGlassWidth() * 0.83 * 2 * $screwCoverMaterial->cost_per_unit;
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('id', 7)->first(); // T-Slot Seal Glazing Bead 7/16
        $tSlotSealGlazingBeatCost = ($this->getGlassWidth() * 2) + ($this->getGlassHeigth() * 2) * 0.83 * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $firstFrameColorLetter)->first(); // STS 0001 (W or B)
        $stopSashCost = ($this->getGlassHeigth() * 2) * 0.83 * $stopSashMaterial->cost_per_unit;
        $settingBlockMaterial = RawMaterial::where('id', 10)->first(); // MATERIAL Setting Block
        $settingBlockCost = 8 * $settingBlockMaterial->cost_per_unit;
        
        //GET OTHER BILLS
        $workBill = config('custom.work_bill');
        $rentBill = config('custom.rent_bill');
        $electricityBill = config('custom.electricity_bill');
        $internetBill = config('custom.internet_bill');
        $otherBill = config('custom.other_bill');

        // TODO: ADD GLASS COST
        $unitPriceCost = 
          $frameHeadCost + 
          $jambCost + 
          $screwMaterialCost + 
          $glazingBeadVerticalCost + 
          $glazingBeadHorizontalCost + 
          $screwCoverCost + 
          $tSlotSealGlazingBeatCost +
          $stopSashCost +
          $settingBlockCost +
          $workBill +
          $rentBill +
          $electricityBill +
          $internetBill +
          $otherBill;

        //GET COMPANY MOCKUP
        $companyMockup = $this->getCompanyMockup();
        $companyMockupCost = $unitPriceCost * $companyMockup / 100;

        $promotion = $this->getCompanyPromotion();
        $promotionCost = $unitPriceCost * $promotion / 100;

        return $unitPriceCost + $companyMockupCost - $promotionCost;
    }
}