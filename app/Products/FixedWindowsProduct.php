<?php

namespace App\Products;

use App\Interfaces\IProduct;
use App\Models\RawMaterial;
use App\Traits\Product;
use App\Traits\Fractions;

class FixedWindowsProduct implements IProduct {

    use Product, Fractions;
    public $width;
    public $height;
    public $frameColor;
    public $glassType;
    public $materialColor;
    
    public function __construct($width, $height, $frameColor, $glassType) {
        $this->width = (float) $width;
        $this->height = (float) $height;
        $this->frameColor = $frameColor;
        $this->glassType = $glassType;
        $this->materialColor = $this->getMaterialColor($frameColor);
    }

    public function getGlassHeigth() {
      return round($this->height - 1.875, 3);
    }

    public function getGlassWidth() {
      return round($this->width - 4.312 - 0.065, 3);
    }

    public function getJamb() {
      return round($this->height - 1.37, 3);
    }

    public function getGlazingBeatVertical() {
      return round($this->getGlassHeigth() - 0.87 + 0.375, 3);
    }

    public function getGlazingBeatHorizontal() {
      return round($this->getGlassWidth() + 0.1875, 3);
    }

    public function getMaterialConsumption($qty) {
      return [
        'VW 101 ' . $this->materialColor => $this->width * 2 * $qty * 0.083,
        'VW 103 ' . $this->materialColor => $this->getJamb() * 2 * $qty * 0.083,
        'VW 108 ' . $this->materialColor => ($this->getGlazingBeatVertical() * 2 + $this->getGlazingBeatHorizontal() * 2) * $qty * 0.083,
        'SC 0001 ' . $this->materialColor => ($this->getGlassWidth() * 2) * $qty * 0.083,
        'TSG 0003' => (($this->getGlazingBeatHorizontal() * 2) + ($this->getGlazingBeatVertical() * 2)) * $qty * 0.083,
        'STS 0001 ' . $this->materialColor => ($this->getGlassHeigth() * 2) * $qty * 0.083,
        'NE 850125' => 8 * $qty,
        'PPA 08-1' => 12 * $qty,
      ];
    }

    public function getCuttingList($qty) {
      $cuttingListResult = [];
      $cuttingListResult[] = $this->getCuttingListObject('Glass', $this->glassType, $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getGlassHeigth()));
      $cuttingListResult[] = $this->getCuttingListObject('Frame Head', 'VW 101 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->width));
      $cuttingListResult[] = $this->getCuttingListObject('Jamb', 'VW 103 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getJamb()));
      $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Vertical', 'VW 108 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getGlazingBeatVertical()));
      $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Horizontal', 'VW 108 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getGlazingBeatHorizontal()));
      $cuttingListResult[] = $this->getCuttingListObject('Screw Cover', 'SC 0001 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getGlassWidth()));
      $cuttingListResult[] = $this->getCuttingListObject('Stop Sash', 'STS 0001 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getGlassHeigth()));

      return $cuttingListResult;
    }

    public function getUnitPrice() {
        $frameHeadMaterial = RawMaterial::where('name', 'VW 101 ' . $this->materialColor)->first(); // MATERIAL 101
        $frameHeadCost = $this->width * 0.083 * $frameHeadMaterial->cost_per_unit * 2;
        $jambMaterial = RawMaterial::where('name', 'VW 103 ' . $this->materialColor)->first(); // MATERIAL 103
        $jambCost = ($this->getJamb() * 0.083) * $jambMaterial->cost_per_unit * 2;
        $screwMaterial = RawMaterial::where('id', 4)->first(); // MATERIAL Screws 8x1
        $screwMaterialCost = $screwMaterial->cost_per_unit * 12;
        $glazingBeadMaterial = RawMaterial::where('name', 'VW 108 ' . $this->materialColor)->first(); // MATERIAL 108
        $glazingBeadVerticalCost = $this->getGlazingBeatVertical() * 0.083 * $glazingBeadMaterial->cost_per_unit * 2;
        $glazingBeadHorizontalCost = $this->getGlazingBeatHorizontal() * 0.083 * $glazingBeadMaterial->cost_per_unit * 2;
        $screwCoverMaterial = RawMaterial::where('name', 'SC 0001 ' . $this->materialColor)->first(); // SC 001 (W or B)
        $screwCoverCost =  $this->getGlassWidth() * 0.083 * 2 * $screwCoverMaterial->cost_per_unit;
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('name', 'TSG 0003')->first(); // MATERIAL TSG 0003
        $tSlotSealGlazingBeatCost = (($this->getGlazingBeatHorizontal() * 2) + ($this->getGlazingBeatVertical() * 2)) * 0.083 * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $this->materialColor)->first(); // STS 0001 (W or B)
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