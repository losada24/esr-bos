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
    public $muntinPanels;
    public $panelA;
    public $muntinPattern;
    public $muntinInteriorStyle;
    public $muntinExteriorStyle;
    public $verticalLines;
    public $horizontalLines;
    
    public function __construct(
      $width, 
      $height, 
      $frameColor, 
      $glassType,
      $muntinPanels = false,
      $panelA = false,
      $muntinPattern = "",
      $muntinInteriorStyle = "",
      $muntinExteriorStyle = "",
      $verticalLines = 0,
      $horizontalLines = 0
    ) {

        if ($width > 53 && $width > $height) {
          $this->height = $width;
          $this->width = $height;
        } else {
          $this->width = (float) $width;
          $this->height = (float) $height;
        }
        
        $this->frameColor = $frameColor;
        $this->glassType = $glassType;
        $this->materialColor = $this->getMaterialColor($frameColor);
        $this->muntinPanels = $muntinPanels;
        $this->panelA = $panelA;
        $this->muntinPattern = $muntinPattern;
        $this->muntinInteriorStyle = $muntinInteriorStyle;
        $this->muntinExteriorStyle = $muntinExteriorStyle;
        $this->verticalLines = $verticalLines;
        $this->horizontalLines = $horizontalLines;
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

    public function getScrewCover() {
      return round($this->width - 3.437, 3);
    }

    public function getMaterialRelease($qty) {
      return [];
    }

    public function getMaterialConsumption($qty) {
      $vw101 = RawMaterial::where('name', 'VW 101 ' . $this->materialColor)->first();
      $vw103 = RawMaterial::where('name', 'VW 103 ' . $this->materialColor)->first();
      $vw108 = RawMaterial::where('name', 'VW 108 ' . $this->materialColor)->first();
      $sc0001 = RawMaterial::where('name', 'SC 0001 ' . $this->materialColor)->first();
      $sts0001 = RawMaterial::where('name', 'STS 0001 ' . $this->materialColor)->first();
      $tsg0003 = RawMaterial::where('name', 'TSG 0003')->first();
      $ne850125 = RawMaterial::where('name', 'NE 850125')->first();
      $ppa081 = RawMaterial::where('name', 'PPA 08-1')->first();

      return [
        'VW 101 ' . $this->materialColor => [
          'amount' => $this->width * 2 * $qty * 0.083,
          'unit_of_measurement' => $vw101->unit_of_measurement,
          'storage_measure' => $vw101->storage_measure,
          'notes' => $vw101->notes,
        ],
        'VW 103 ' . $this->materialColor => [
          'amount' => $this->getJamb() * 2 * $qty * 0.083,
          'unit_of_measurement' => $vw103->unit_of_measurement,
          'storage_measure' => $vw103->storage_measure,
          'notes' => $vw103->notes,
        ],
        'VW 108 ' . $this->materialColor => [
          'amount' => ($this->getGlazingBeatVertical() * 2 + $this->getGlazingBeatHorizontal() * 2) * $qty * 0.083,
          'unit_of_measurement' => $vw108->unit_of_measurement,
          'storage_measure' => $vw108->storage_measure,
          'notes' => $vw108->notes,
        ],
        'SC 0001 ' . $this->materialColor => [
          'amount' => ($this->getScrewCover() * 2) * $qty * 0.083,
          'unit_of_measurement' => $sc0001->unit_of_measurement,
          'storage_measure' => $sc0001->storage_measure,
          'notes' => $sc0001->notes,
        ],
        'TSG 0003' => [
          'amount' => (($this->getGlazingBeatHorizontal() * 2) + ($this->getGlazingBeatVertical() * 2)) * $qty * 0.083,
          'unit_of_measurement' => $tsg0003->unit_of_measurement,
          'storage_measure' => $tsg0003->storage_measure,
          'notes' => $tsg0003->notes,
        ],
        'STS 0001 ' . $this->materialColor => [
          'amount' => ($this->getGlassHeigth() * 2) * $qty * 0.083,
          'unit_of_measurement' => $sts0001->unit_of_measurement,
          'storage_measure' => $sts0001->storage_measure,
          'notes' => $sts0001->notes,
        ],
        'NE 850125' => [
          'amount' => 8 * $qty,
          'unit_of_measurement' => $ne850125->unit_of_measurement,
          'storage_measure' => $ne850125->storage_measure,
          'notes' => $ne850125->notes,
        ],
        'PPA 08-1' => [
          'amount' => 12 * $qty,
          'unit_of_measurement' => $ppa081->unit_of_measurement,
          'storage_measure' => $ppa081->storage_measure,
          'notes' => $ppa081->notes,
        ],
      ];
    }

    public function getCuttingList($qty) {
      $cuttingListResult = [];
      $cuttingListResult[] = $this->getCuttingListObject('Frame Head/Sill', 'VW 101 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->width), $this->width);
      $cuttingListResult[] = $this->getCuttingListObject('Jamb', 'VW 103 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getJamb()), $this->getJamb());
      $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Vertical', 'VW 108 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getGlazingBeatVertical()), $this->getGlazingBeatVertical());
      $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Horizontal', 'VW 108 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getGlazingBeatHorizontal()), $this->getGlazingBeatHorizontal());
      $cuttingListResult[] = $this->getCuttingListObject('Screw Cover', 'SC 0001 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getScrewCover()), $this->getScrewCover());
      $cuttingListResult[] = $this->getCuttingListObject('Stop Sash', 'STS 0001 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getGlassHeigth()), $this->getGlassHeigth());
      $cuttingListResult[] = $this->getCuttingListObject('Glass', $this->glassType, $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getGlassHeigth()), 0);

      return $cuttingListResult;
    }

    public function getPoGlass($qty) {
      $cuttingListResult = [];
      $cuttingListResult[] = $this->getCuttingListObject('Glass', $this->glassType, $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getGlassHeigth()));
      return $cuttingListResult;
    }

    public function getUnitPrice() {
        $frameHeadMaterial = RawMaterial::where('name', 'VW 101 ' . $this->materialColor)->first(); // MATERIAL 101
        $frameHeadCost = $this->width * 0.083 * $frameHeadMaterial->cost_per_unit * 2;
        $jambMaterial = RawMaterial::where('name', 'VW 103 ' . $this->materialColor)->first(); // MATERIAL 103
        $jambCost = ($this->getJamb() * 0.083) * $jambMaterial->cost_per_unit * 2;
        $screwMaterial = RawMaterial::where('name', 'PPA 08-1')->first(); // MATERIAL Screws 8x1
        $screwMaterialCost = $screwMaterial->cost_per_unit * 12;
        $glazingBeadMaterial = RawMaterial::where('name', 'VW 108 ' . $this->materialColor)->first(); // MATERIAL 108
        $glazingBeadVerticalCost = $this->getGlazingBeatVertical() * 0.083 * $glazingBeadMaterial->cost_per_unit * 2;
        $glazingBeadHorizontalCost = $this->getGlazingBeatHorizontal() * 0.083 * $glazingBeadMaterial->cost_per_unit * 2;
        $screwCoverMaterial = RawMaterial::where('name', 'SC 0001 ' . $this->materialColor)->first(); // SC 001 (W or B)
        $screwCoverCost =  $this->getScrewCover()* 0.083 * 2 * $screwCoverMaterial->cost_per_unit;
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('name', 'TSG 0003')->first(); // MATERIAL TSG 0003
        $tSlotSealGlazingBeatCost = (($this->getGlazingBeatHorizontal() * 2) + ($this->getGlazingBeatVertical() * 2)) * 0.083 * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $this->materialColor)->first(); // STS 0001 (W or B)
        $stopSashCost = ($this->getGlassHeigth() * 2) * 0.083 * $stopSashMaterial->cost_per_unit;
        $settingBlockMaterial = RawMaterial::where('name', 'NE 850125')->first(); // MATERIAL NE 850125
        $settingBlockCost = 1 * $settingBlockMaterial->cost_per_unit;
        $structuralSiliconeMaterial = RawMaterial::where('id', 32)->first(); // MATERIAL Structural Silicone
        $structuralSiliconeCost = (((($this->getGlassHeigth() - 0.87 + 0.3775) * 2) * 0.083) + ((($this->getGlassWidth() + 0.1875) * 2) * 0.083)) * $structuralSiliconeMaterial->cost_per_unit;
        $glassMaterial = RawMaterial::where('name', $this->glassType)->first(); // MATERIAL Glass
        $glassCost = $this->getGlassSize($this->getGlassHeigth()) * $this->getGlassSize($this->getGlassWidth()) / 144 * $glassMaterial->cost_per_unit;

        //GET OTHER BILLS
        $workBill = config('custom.work_bill');
        $rentBill = config('custom.rent_bill');
        $electricityBill = config('custom.electricity_bill');
        $internetBill = config('custom.internet_bill');
        $otherBill = config('custom.other_bill');
        $packing = config('custom.packing');
        $cornerSilicone = config('custom.corner_silicone');

        //GET MUNTIN COST
        $muntinPriceBySqft = config('custom.muntin_price_by_sqft');
        $muntinCost = 0;
        if ($this->muntinPanels) {
          $horizontalMuntinsCost = ($this->horizontalLines - 1) * $this->getGlassWidth() * $muntinPriceBySqft * 0.083;
          $verticalMuntingCost = ($this->verticalLines - 1) * $this->getGlassHeigth() * $muntinPriceBySqft * 0.083;

          if (!empty($this->muntinInteriorStyle)) {
            $muntinCost = $horizontalMuntinsCost + $verticalMuntingCost;
          }
          if(!empty($this->muntinExteriorStyle)) {
            $muntinCost += $horizontalMuntinsCost + $verticalMuntingCost;
          }
        }

        /*echo "Precio del Cristal:" . $glassMaterial->cost_per_unit . "<br/>";
        echo "Glass Width:" . $this->getGlassWidth() . "<br/>"; 
        echo "Glass Height:" . $this->getGlassHeigth() . "<br/>"; 
        echo "Pulgada Height:" . $this->getGlassSize($this->getGlassHeigth()) . "<br/>";
        echo "Pulgada Width:" . $this->getGlassSize($this->getGlassWidth()) . "<br/>";
        echo "Frame Head Cost: " . $frameHeadCost . "<br>";
        echo "jambCost: " . $jambCost . "<br>";
        echo "screwMaterialCost: " . $screwMaterialCost . "<br>";
        echo "glazingBeadVerticalCost: " . $glazingBeadVerticalCost . "<br>";
        echo "glazingBeadHorizontalCost: " . $glazingBeadHorizontalCost . "<br>";
        echo "screwCoverCost: " . $screwCoverCost . "<br>";
        echo "tSlotSealGlazingBeatCost: " . $tSlotSealGlazingBeatCost . "<br>";
        echo "stopSashCost: " . $stopSashCost . "<br>";
        echo "settingBlockCost: " . $settingBlockCost . "<br>";
        echo "structuralSiliconeCost: " . $structuralSiliconeCost . "<br>";
        echo "glassCost: " . $glassCost . "<br>";
        echo "MuntinCost: " . $muntinCost . "<br>";
        echo 'Suma Total: ' . round(
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
            $muntinCost +
            $glassCost, 2);
        die; */

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
          $otherBill +
          $cornerSilicone +
          $muntinCost +
          $packing;
          
        //GET COMPANY MOCKUP
        /*$companyMockup = $this->getCompanyMockup();
        $companyMockupCost = $unitPriceCost * $companyMockup / 100;

        $promotion = $this->getCompanyPromotion();
        $promotionCost = ($unitPriceCost + $companyMockupCost) * $promotion / 100;
        
         echo "unitPriceCost: " . $unitPriceCost . "<br>";
        echo "Promotion: " . $promotionCost . "<br>";
        echo "Mockup: " . $companyMockupCost . "<br>";
        echo "associate cost: " . ($workBill + $rentBill + $electricityBill + $internetBill + $otherBill) . "<br>";
        echo "Valor Final cost: " . round($unitPriceCost + $companyMockupCost - $promotionCost, 2) . "<br>";
        die;*/
        return round($unitPriceCost, 2);
    }
}