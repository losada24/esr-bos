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
    public $glassType;
    
    public function __construct($width, $height, $frameColor, $glassType) {
        $this->width = $width;
        $this->height = $height;
        $this->frameColor = $frameColor;
        $this->glassType = $glassType;
    }

    public function getGlassHeigth() {
      return $this->height - 1.875;
    }

    public function getGlassWidth() {
      return $this->width - 4.312 - 0.065;
    }

    public function getUnitPrice() {
        // echo "<pre>";
        $frameHeadMaterial = RawMaterial::where('id', 1)->first(); // MATERIAL 101
        $frameHeadCost = $this->width * 0.083 * $frameHeadMaterial->cost_per_unit * 2;
        // echo "101: " . $frameHeadCost . "<br/>";
        $jambMaterial = RawMaterial::where('id', 2)->first(); // MATERIAL 103
        $jambCost = (($this->height - 1.37) * 0.083) * $jambMaterial->cost_per_unit * 2;
        // echo "103: " . $jambCost . "<br/>";
        $screwMaterial = RawMaterial::where('id', 4)->first(); // MATERIAL Screws 8x1
        $screwMaterialCost = $screwMaterial->cost_per_unit * 12;
        // echo "Screws 8x1: " . $screwMaterialCost . "<br/>";
        $glazingBeadMaterial = RawMaterial::where('id', 3)->first(); // MATERIAL 108
        $glazingBeadVerticalCost = ($this->getGlassHeigth() - 0.87 + 0.3775) * 0.083 * $glazingBeadMaterial->cost_per_unit * 2;
        $glazingBeadHorizontalCost = ($this->getGlassWidth() + 0.1875) * 0.083 * $glazingBeadMaterial->cost_per_unit * 2;
        // echo "108h: " . $glazingBeadHorizontalCost . "<br/>";
        // echo "108v: " . $glazingBeadVerticalCost . "<br/>";
        $firstFrameColorLetter = substr($this->frameColor, 0, 1);
        $screwCoverMaterial = RawMaterial::where('name', 'SC 001 ' . $firstFrameColorLetter)->first(); // SC 001 (W or B)
        $screwCoverCost =  $this->getGlassWidth() * 0.083 * 2 * $screwCoverMaterial->cost_per_unit;
        // echo "SC001: " . $screwCoverCost . "<br/>";
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('id', 7)->first(); // T-Slot Seal Glazing Bead 7/16
        $tSlotSealGlazingBeatCost = (((($this->getGlassHeigth() - 0.87 + 0.3775) * 2) * 0.083) + ((($this->getGlassWidth() + 0.1875) * 2) * 0.083)) * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        // echo "T-Slot Seal Glazing: " . $tSlotSealGlazingBeatCost . "<br/>";
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $firstFrameColorLetter)->first(); // STS 0001 (W or B)
        $stopSashCost = ($this->getGlassHeigth() * 2) * 0.083 * $stopSashMaterial->cost_per_unit;
        // echo "STS 0001: " . $stopSashCost . "<br/>";
        $settingBlockMaterial = RawMaterial::where('id', 10)->first(); // MATERIAL Setting Block
        $settingBlockCost = 8 * $settingBlockMaterial->cost_per_unit;
        // echo "Setting Block: " . $settingBlockCost . "<br/>";
        $structuralSiliconeMaterial = RawMaterial::where('id', 32)->first(); // MATERIAL Structural Silicone
        $structuralSiliconeCost = (((($this->getGlassHeigth() - 0.87 + 0.3775) * 2) * 0.083) + ((($this->getGlassWidth() + 0.1875) * 2) * 0.083)) * $structuralSiliconeMaterial->cost_per_unit;
        // echo "Structural Silicone: " . $structuralSiliconeCost . "<br/>";
        $glassMaterial = RawMaterial::where('name', $this->glassType)->first(); // MATERIAL Glass
        $glassCost = $this->getGlassHeigth() * $this->getGlassWidth() / 144 * $glassMaterial->cost_per_unit;
        // echo "Glass: " . $glassCost . "<br/>";
        //GET OTHER BILLS
        $workBill = config('custom.work_bill');
        $rentBill = config('custom.rent_bill');
        $electricityBill = config('custom.electricity_bill');
        $internetBill = config('custom.internet_bill');
        $otherBill = config('custom.other_bill');

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
          $structuralSiliconeCost +
          $glassCost +
          $workBill +
          $rentBill +
          $electricityBill +
          $internetBill +
          $otherBill;

        // echo "Unit Price Cost: " . $unitPriceCost . "<br/>";
        // echo "</pre>";
        //die;
        //GET COMPANY MOCKUP
        $companyMockup = $this->getCompanyMockup();
        $companyMockupCost = $unitPriceCost * $companyMockup / 100;

        $promotion = $this->getCompanyPromotion();
        $promotionCost = $unitPriceCost * $promotion / 100;

        return $unitPriceCost + $companyMockupCost - $promotionCost;
    }
}