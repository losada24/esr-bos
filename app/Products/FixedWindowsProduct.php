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

    public function getJamb() {
      return $this->height - 1.37;
    }

    public function getGlazingBeatVertical() {
      return $this->getGlassHeigth() - 0.87 + 0.375;
    }

    public function getGlazingBeatHorizontal() {
      return $this->getGlassWidth() + 0.1875;
    }

    public function getCuttingList($qty) {
      $cuttingListResult = [];
      $materialColor = $this->getMaterialColor($this->frameColor);
      $cuttingListResult[] = $this->getCuttingListObject('Glass', $this->glassType, $qty, $this->getGlassWidth() . 'x' . $this->getGlassHeigth());
      $cuttingListResult[] = $this->getCuttingListObject('Frame Head', 'VW 101 ' . $materialColor, 2 * $qty, $this->width);
      $cuttingListResult[] = $this->getCuttingListObject('Jamb', 'VW 103 ' . $materialColor, 2 * $qty, $this->getJamb());
      $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Vertical', 'VW 108 ' . $materialColor, 2 * $qty, $this->getGlazingBeatVertical());
      $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Horizontal', 'VW 108 ' . $materialColor, 2 * $qty, $this->getGlazingBeatHorizontal());
      $cuttingListResult[] = $this->getCuttingListObject('Screw Cover', 'SC 0001 ' . $materialColor, 2 * $qty, $this->getGlassWidth());
      $cuttingListResult[] = $this->getCuttingListObject('T Slot Seal Glazing Beat 7/16', 'TSG 0003', $qty, ($this->getGlazingBeatHorizontal() * 2) + ($this->getGlazingBeatVertical() * 2));
      $cuttingListResult[] = $this->getCuttingListObject('Stop Sash', 'STS 0001 ' . $materialColor, 2 * $qty, $this->getGlassHeigth());
      $cuttingListResult[] = $this->getCuttingListObject('Setting Block', 'NE 850125', 8 * $qty, $this->getGlassHeigth());
      $cuttingListResult[] = $this->getCuttingListObject('Screws', 'Screws 8x1', 12 * $qty, $this->getGlassHeigth());

      return $cuttingListResult;
    }

    public function getUnitPrice() {
        $firstFrameColorLetter = $this->getMaterialColor($this->frameColor);
        $frameHeadMaterial = RawMaterial::where('name', 'VW 101 ' . $firstFrameColorLetter)->first(); // MATERIAL 101
        $frameHeadCost = $this->width * 0.083 * $frameHeadMaterial->cost_per_unit * 2;
        $jambMaterial = RawMaterial::where('name', 'VW 103 ' . $firstFrameColorLetter)->first(); // MATERIAL 103
        $jambCost = ($this->getJamb() * 0.083) * $jambMaterial->cost_per_unit * 2;
        $screwMaterial = RawMaterial::where('id', 4)->first(); // MATERIAL Screws 8x1
        $screwMaterialCost = $screwMaterial->cost_per_unit * 12;
        $glazingBeadMaterial = RawMaterial::where('name', 'VW 108 ' . $firstFrameColorLetter)->first(); // MATERIAL 108
        $glazingBeadVerticalCost = $this->getGlazingBeatVertical() * 0.083 * $glazingBeadMaterial->cost_per_unit * 2;
        $glazingBeadHorizontalCost = $this->getGlazingBeatHorizontal() * 0.083 * $glazingBeadMaterial->cost_per_unit * 2;
        $screwCoverMaterial = RawMaterial::where('name', 'SC 0001 ' . $firstFrameColorLetter)->first(); // SC 001 (W or B)
        $screwCoverCost =  $this->getGlassWidth() * 0.083 * 2 * $screwCoverMaterial->cost_per_unit;
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('name', 'TSG 0003')->first(); // MATERIAL TSG 0003
        $tSlotSealGlazingBeatCost = (($this->getGlazingBeatHorizontal() * 2) + ($this->getGlazingBeatVertical() * 2)) * 0.083 * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $firstFrameColorLetter)->first(); // STS 0001 (W or B)
        $stopSashCost = ($this->getGlassHeigth() * 2) * 0.083 * $stopSashMaterial->cost_per_unit;
        $settingBlockMaterial = RawMaterial::where('name', 'NE 850125')->first(); // MATERIAL NE 850125
        $settingBlockCost = 8 * $settingBlockMaterial->cost_per_unit;
        $structuralSiliconeMaterial = RawMaterial::where('id', 32)->first(); // MATERIAL Structural Silicone
        $structuralSiliconeCost = (((($this->getGlassHeigth() - 0.87 + 0.3775) * 2) * 0.083) + ((($this->getGlassWidth() + 0.1875) * 2) * 0.083)) * $structuralSiliconeMaterial->cost_per_unit;
        $glassMaterial = RawMaterial::where('name', $this->glassType)->first(); // MATERIAL Glass
        $glassCost = $this->getGlassHeigth() * $this->getGlassWidth() / 144 * $glassMaterial->cost_per_unit;
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
          
        //GET COMPANY MOCKUP
        $companyMockup = $this->getCompanyMockup();
        $companyMockupCost = $unitPriceCost * $companyMockup / 100;

        $promotion = $this->getCompanyPromotion();
        $promotionCost = $unitPriceCost * $promotion / 100;

        return $unitPriceCost + $companyMockupCost - $promotionCost;
    }
}