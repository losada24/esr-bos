<?php

namespace App\Products;

use App\Interfaces\IProduct;
use App\Models\RawMaterial;
use App\Traits\Fractions;
use App\Traits\Product;

class HorizontalRollerProduct implements IProduct {

    use Product, Fractions;
    public $width;
    public $height;
    public $frameColor;
    public $glassType;
    public $screenRequired;
    public $materialColor;
    
    public function __construct($width, $height, $frameColor, $glassType, $screenRequired) {
        $this->width = (float) $width;
        $this->height = (float) $height;
        $this->frameColor = $frameColor;
        $this->glassType = $glassType;
        $this->screenRequired = $screenRequired;
        $this->materialColor = $this->getMaterialColor($frameColor);
    }

    public function getGlassHeigth() {
      return round($this->height - 5.21 - 0.1, 3);
    }

    public function getGlassWidth() {
      return round(($this->width / 2) - (5.25 / 2) - 0.125, 3);
    }

    public function getMoveGlassHeight() {
      return round($this->getGlassHeigth() - 0.25 - 0.15, 3);
    }

    public function getVentJamb() {
      return round($this->height - 5.23 - 0.15, 3);
    }

    public function getVentBottomAndTop() {
      return round($this->getGlassWidth() + 2.188, 3);
    }

    public function getFixedMeetingRail() {
      return round($this->height - 5.188, 3);
    }

    public function getMoveGlazingBead() {
      return round($this->getMoveGlassHeight() + 0.125, 3);
    }

    public function getGlazingBeadWidth() {
      return round($this->getGlassWidth() - 0.75, 3);
    }

    public function getScreenWidth() {
      return round(($this->width / 2) - 0.25, 3);
    }

    public function getScreenHeigth() {
      return round($this->height - 5.5, 3);
    }

    public function getFrameSill() {
      return round($this->width - 1.562, 3);
    }

    public function getSillTrackRail() {
      return round($this->getFrameSill() - 1, 3);
    }

    public function getSteelReiceforment() {
      return round($this->getFixedMeetingRail() - 1, 3);
    }

    public function getWeatherStripMeetRailSash() {
      return round(2 * $this->getVentJamb() + $this->getFixedMeetingRail(), 3);
    }

    public function getTSlotSealGlazingBeat() {
      return round((4 * $this->getGlassWidth()) + (2 * $this->getGlassHeigth()) + (2 * $this->getMoveGlazingBead()), 3);
    }

    public function getMaterialConsumption($qty) {
      return [
          'VW 110 ' . $this->materialColor => $this->getVentJamb() * 2 * $qty * 0.083,
          'VW 106 ' . $this->materialColor => $this->getVentJamb() * $qty * 0.083,
          'VW 107 ' . $this->materialColor => (($this->getVentBottomAndTop() * 2  * $qty) + ($qty * $this->getVentBottomAndTop())) * 0.083,
          'VW 101 ' . $this->materialColor => $this->height * $qty * 0.083,
          'VW 102 ' . $this->materialColor => $this->height * $qty * 0.083,
          'VW 104 ' . $this->materialColor => $this->getFixedMeetingRail() * $qty * 0.083,
          'VW 108 ' . $this->materialColor => (($this->getGlassHeigth() * 2 * $qty) + ($this->getMoveGlazingBead() * 2 * $qty) + ($this->getGlazingBeadWidth() * 4 * $qty)) * 0.083,
          'VW 111 ' . $this->materialColor => ($this->getFrameSill() * 2 * $qty) * 0.083,
          'VW 112 ' . $this->materialColor => $this->getSillTrackRail() * $qty * 0.083,
          'WH 0001 ' . $this->materialColor => 2 * $qty,
          'RH 0001 ' . $this->materialColor => 2 * $qty,
          'ST 0001' => $this->getSteelReiceforment() * $qty * 0.083,
          'VW 109 ' . $this->materialColor => 2 * $qty,
          'STS 0001 ' . $this->materialColor => 3 * $qty,
          'SC 0001 ' . $this->materialColor => $this->getGlassHeigth() * $qty * 0.083,
          'SS 0001 ' . $this->materialColor => ($this->getVentBottomAndTop() * 2 * $qty) * 0.083,
          'NE 850125' => 16 * $qty,
          'W 22184 ' . $this->materialColor => $this->getWeatherStripMeetRailSash() * $qty * 0.083,
          'w 22254 ' . $this->materialColor => $this->height * $qty * 0.083,
          'TSG 0003' => $this->getTSlotSealGlazingBeat() * $qty * 0.083,
          'TSB 0001' => $this->height * $qty * 0.083,
      ];
    }

    public function getCuttingList($qty) {
        $cuttingListResult = [];
        $cuttingListResult[] = $this->getCuttingListObject('Glass Fixed', $this->glassType, $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getGlassHeigth()));
        $cuttingListResult[] = $this->getCuttingListObject('Glass Move', $this->glassType, $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getMoveGlassHeight()));
        $cuttingListResult[] = $this->getCuttingListObject('Vent Jamb', 'VW 110 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getVentJamb()));
        $cuttingListResult[] = $this->getCuttingListObject('Vent Jamb', 'VW 106 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getVentJamb()));
        $cuttingListResult[] = $this->getCuttingListObject('Vent Bottom', 'VW 107 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getVentBottomAndTop()));
        $cuttingListResult[] = $this->getCuttingListObject('Vent Top', 'VW 107 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getVentBottomAndTop()));
        $cuttingListResult[] = $this->getCuttingListObject('Jamb', 'VW 101 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->height));
        $cuttingListResult[] = $this->getCuttingListObject('Jamb', 'VW 102 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->height));
        $cuttingListResult[] = $this->getCuttingListObject('Punch M.R', ' - ', '-', $this->getNumberWithFraction($this->getGlassWidth() + 0.44));
        $cuttingListResult[] = $this->getCuttingListObject('Fixed Meeting Rail', 'VW 104 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getFixedMeetingRail()));
        $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Fix Vertical', 'VW 108 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getGlassHeigth()));
        $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Move Vertical', 'VW 108 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getMoveGlazingBead()));
        $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Width Horizontal', 'VW 108 ' . $this->materialColor, 4 * $qty, $this->getNumberWithFraction($this->getGlazingBeadWidth()));
        $cuttingListResult[] = $this->getCuttingListObject('Frame Head', 'VW 111 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getFrameSill()));
        $cuttingListResult[] = $this->getCuttingListObject('Sill Track Rail', 'VW 112 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getSillTrackRail()));
        $cuttingListResult[] = $this->getCuttingListObject('Steel Reiceforcement square', 'ST 0001', $qty, $this->getNumberWithFraction($this->getSteelReiceforment()));
        $cuttingListResult[] = $this->getCuttingListObject('Screw Cover', 'SC 0001 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getGlassHeigth()));
        $cuttingListResult[] = $this->getCuttingListObject('Side Sash Clip', 'SS 0001 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getVentBottomAndTop()));
        //SILICONE
        if ($this->screenRequired) {
          $cuttingListResult[] = $this->getCuttingListObject('Screen', '', $qty, $this->getNumberWithFraction($this->getScreenWidth()) . ' x ' . $this->getNumberWithFraction($this->getScreenHeigth()));
        }

        return $cuttingListResult;
    }

    public function getUnitPrice() {
        $firstFrameColorLetter = $this->getMaterialColor($this->frameColor);
        $ventJamb106Material = RawMaterial::where('name', 'VW 106 ' . $firstFrameColorLetter)->first(); // MATERIAL 106
        $ventJamb106Cost = $this->getVentJamb() * 0.083 * $ventJamb106Material->cost_per_unit;
        $ventJamb110Material = RawMaterial::where('name', 'VW 110 ' . $firstFrameColorLetter)->first(); // MATERIAL 110
        $ventJamb110Cost = $this->getVentJamb() * 0.083 * $ventJamb110Material->cost_per_unit;
        $ventBottomMaterial = RawMaterial::where('name', 'VW 107 ' . $firstFrameColorLetter)->first(); // MATERIAL 107,
        $ventBottomCost = $this->getVentBottomAndTop() * 0.083 * $ventBottomMaterial->cost_per_unit * 2;
        $ventTopCost = $this->getVentBottomAndTop() * 0.083 * $ventBottomMaterial->cost_per_unit;
        $jamb101Material = RawMaterial::where('name', 'VW 101 ' . $firstFrameColorLetter)->first(); // MATERIAL 101
        $jamb101Cost = $this->height * 0.083 * $jamb101Material->cost_per_unit;
        $jamb102Material = RawMaterial::where('name', 'VW 102 ' . $firstFrameColorLetter)->first(); // MATERIAL 102
        $jamb102Cost = $this->height * 0.083 * $jamb102Material->cost_per_unit;
        $fixedMeetingRailMaterial = RawMaterial::where('name', 'VW 104 ' . $firstFrameColorLetter)->first(); // MATERIAL 104
        $fixedMeetingRailMaterialCost = $this->getFixedMeetingRail() * 0.083 * $fixedMeetingRailMaterial->cost_per_unit;
        $frameHeadSillMaterial = RawMaterial::where('name', 'VW 111 ' . $firstFrameColorLetter)->first(); // MATERIAL 111
        $frameHeadSillCost = $this->getFrameSill() * 0.083 * $frameHeadSillMaterial->cost_per_unit * 2;
        $stilTrackRailMaterial = RawMaterial::where('name', 'VW 112 ' . $firstFrameColorLetter)->first(); // MATERIAL 112
        $stilTrackRailCost = $this->getSillTrackRail() * 0.083 * $stilTrackRailMaterial->cost_per_unit;
        $weepHoleMaterial = RawMaterial::where('name', 'WH 0001 ' . $firstFrameColorLetter)->first(); // MATERIAL WH 0001 (W or B)
        $weepHoleCost = 2 * $weepHoleMaterial->cost_per_unit;
        $rolesHousingMaterial = RawMaterial::where('id', 31)->first(); // MATERIAL RH 0001
        $rolesHousingCost = 2 * $rolesHousingMaterial->cost_per_unit;
        $steelReinforcementMaterial = RawMaterial::where('name', 'ST 0001')->first(); // MATERIAL ST 0001
        $steelReinforcementCost = $this->getSteelReiceforment() * 0.083 * $steelReinforcementMaterial->cost_per_unit;
        $ventLatchMaterial = RawMaterial::where('name', 'VW 109 ' . $firstFrameColorLetter)->first(); // MATERIAL 109
        $ventLatchCost = 3 * 0.083 * $ventLatchMaterial->cost_per_unit * 2;
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $firstFrameColorLetter)->first(); // MATERIAL STS 0001 (W or B)
        $stopSashCost = 3 * 0.083 * $stopSashMaterial->cost_per_unit * 2;
        $screwCoverMaterial = RawMaterial::where('name', 'SC 0001 ' . $firstFrameColorLetter)->first(); // SC 0001 (W or B)
        $screwCoverCost =  $this->getGlassHeigth() * 0.083 * $screwCoverMaterial->cost_per_unit;
        $sideSashClipMaterial = RawMaterial::where('name', 'SS 0001 ' . $firstFrameColorLetter)->first(); // MATERIAL SS 0001 (W or B)
        $sideSashClipCost = ($this->getGlassWidth() + 2.188) * 0.083 * $sideSashClipMaterial->cost_per_unit * 2;
        $settingBlockMaterial = RawMaterial::where('id', 10)->first(); // MATERIAL NE850062
        $settingBlockCost = 16 * $settingBlockMaterial->cost_per_unit;
        $weatherStripMeetRailSashMaterial = RawMaterial::where('name', 'W 22184 ' . $firstFrameColorLetter)->first(); // MATERIAL W 22184 (W or B)
        $weatherStripMeetRailSashCost = (2 * ($this->height - 5.23 - 0.15) + ($this->width - 5.188)) * 0.083 * $weatherStripMeetRailSashMaterial->cost_per_unit;
        $wheatherStripBottomMaterial = RawMaterial::where('name', 'W 22254 ' . $firstFrameColorLetter)->first(); // MATERIAL W 22254 (W or B)
        $wheatherStripBottomCost = $this->height * 0.083 * $wheatherStripBottomMaterial->cost_per_unit;
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('id', 7)->first(); // TSG 0003
        $tSlotSealGlazingBeatCost = ((4 * $this->getGlassHeigth()) + (4 * $this->getGlassWidth())) * 0.083 * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        $tSlotSealFrameBottomMaterial = RawMaterial::where('id', 19)->first(); // TSB 0001
        $tSlotSealFrameBottomCost = $this->height * 0.083 * $tSlotSealFrameBottomMaterial->cost_per_unit;
        $glazingBeatMaterial = RawMaterial::where('name', 'VW 108 ' . $firstFrameColorLetter)->first(); // MATERIAL 108
        $glazingBeatWidthCost = 4 * $this->getGlazingBeadWidth() * 0.083 * $glazingBeatMaterial->cost_per_unit;             
        $glazingBeatHeigthCost = ((2 * $this->getGlassHeigth()) + (2 * $this->getMoveGlazingBead())) * 0.083 * $glazingBeatMaterial->cost_per_unit;

        $structuralSliconeMaterial = RawMaterial::where('id', 32)->first(); // MATERIAL 105//STRUCTURAL SILICONE
        $structuralSliconeMaterialCost = (($this->getGlassWidth() * 4) + ($this->getGlassHeigth() * 4)) * 0.083 * $structuralSliconeMaterial->cost_per_unit;

        // GET OTHER BILLS
        $workBill = config('custom.work_bill');
        $rentBill = config('custom.rent_bill');
        $electricityBill = config('custom.electricity_bill');
        $internetBill = config('custom.internet_bill');
        $otherBill = config('custom.other_bill');
        $screen_price_by_sqft = config('custom.screen_price_by_sqft');
        $glassMaterial = RawMaterial::where('name', $this->glassType)->first(); // MATERIAL Glass
        $glassCost = $this->getGlassHeigth() * $this->getGlassWidth() / 144 * $glassMaterial->cost_per_unit;
        $glassCostMove = $this->getMoveGlassHeight() * $this->getGlassWidth() / 144 * $glassMaterial->cost_per_unit;
        $screenCost = 0;
        if ($this->screenRequired) {
          $screenCost = $this->getScreenWidth() * $this->getScreenHeigth() / 144 * $screen_price_by_sqft;
        }
        $unitPriceCost = 
          $ventJamb106Cost +
          $ventJamb110Cost +
          $ventBottomCost +
          $ventTopCost +
          $jamb101Cost +
          $jamb102Cost +
          $fixedMeetingRailMaterialCost +
          $frameHeadSillCost +
          $stilTrackRailCost +
          $weepHoleCost +
          $rolesHousingCost +
          $steelReinforcementCost +
          $ventLatchCost +
          $stopSashCost +
          $screwCoverCost +
          $sideSashClipCost +
          $settingBlockCost +
          $weatherStripMeetRailSashCost +
          $wheatherStripBottomCost +
          $tSlotSealGlazingBeatCost +
          $tSlotSealFrameBottomCost +
          $glazingBeatWidthCost +
          $glazingBeatHeigthCost +
          $structuralSliconeMaterialCost +
          $glassCost +
          $glassCostMove +
          $screenCost +
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