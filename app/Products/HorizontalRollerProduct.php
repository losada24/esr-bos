<?php

namespace App\Products;

use App\Interfaces\IProduct;
use App\Models\RawMaterial;
use App\Traits\Product;

class HorizontalRollerProduct implements IProduct {

    use Product;
    public $width;
    public $height;
    public $frameColor;
    public $glassType;
    public $screenRequired;
    
    public function __construct($width, $height, $frameColor, $glassType, $screenRequired/* , $line_item_name, $glass, $qty, $markup */) {
        $this->width = $width;
        $this->height = $height;
        $this->frameColor = $frameColor;
        $this->glassType = $glassType;
        $this->screenRequired = $screenRequired;
    }

    public function getGlassHeigth() {
      return $this->height - 5.21 - 0.1;
    }

    public function getGlassWidth() {
      return ($this->width / 2) - (5.25 / 2) - 0.125;
    }

    public function getMoveGlassWidth() {
      return $this->getGlassHeigth() - 0.25 - 0.15;
    }

    public function getUnitPrice() {
        $firstFrameColorLetter = substr($this->frameColor, 0, 1);
        $ventJamb106Material = RawMaterial::where('name', 'VW 106 ' . $firstFrameColorLetter)->first(); // MATERIAL 106
        $ventJamb106Cost = ($this->height - 5.23 - 0.15) * 0.83 * $ventJamb106Material->cost_per_unit;
        $ventJamb110Material = RawMaterial::where('name', 'VW 104 ' . $firstFrameColorLetter)->first(); // MATERIAL 110
        $ventJamb110Cost = ($this->height - 5.23 - 0.15) * 0.83 * $ventJamb110Material->cost_per_unit;
        $ventBottomMaterial = RawMaterial::where('name', 'VW 107 ' . $firstFrameColorLetter)->first(); // MATERIAL 107,
        $ventBottomCost = ($this->getGlassWidth() + 2.188) * 0.83 * $ventBottomMaterial->cost_per_unit * 2;
        $ventTopCost = ($this->getGlassWidth() + 2.188) * 0.83 * $ventBottomMaterial->cost_per_unit;
        $jamb101Material = RawMaterial::where('name', 'VW 101 ' . $firstFrameColorLetter)->first(); // MATERIAL 101
        $jamb101Cost = $this->height * 0.83 * $jamb101Material->cost_per_unit * 2;
        $jamb102Material = RawMaterial::where('name', 'VW 102 ' . $firstFrameColorLetter)->first(); // MATERIAL 102
        $jamb102Cost = $this->height * 0.83 * $jamb102Material->cost_per_unit;
        $fixedMeetingRailMaterial = RawMaterial::where('name', 'VW 104 ' . $firstFrameColorLetter)->first(); // MATERIAL 104
        $fixedMeetingRailMaterialCost = ($this->height - 5.188) * 0.83 * $fixedMeetingRailMaterial->cost_per_unit;
        $frameHeadSillMaterial = RawMaterial::where('name', 'VW 111 ' . $firstFrameColorLetter)->first(); // MATERIAL 111
        $frameHeadSillCost = ($this->width - 1.562) * 0.83 * $frameHeadSillMaterial->cost_per_unit * 2;
        $stilTrackRailMaterial = RawMaterial::where('name', 'VW 112 ' . $firstFrameColorLetter)->first(); // MATERIAL 112
        $stilTrackRailCost = ($this->width - 1.562 - 1) * 0.83 * $stilTrackRailMaterial->cost_per_unit;
        $weepHoleMaterial = RawMaterial::where('name', 'WH 0001 ' . $firstFrameColorLetter)->first(); // MATERIAL WH 0001 (W or B)
        $weepHoleCost = 2 * $weepHoleMaterial->cost_per_unit;
        $rolesHousingMaterial = RawMaterial::where('id', 31)->first(); // MATERIAL RH 0001
        $rolesHousingCost = 2 * $rolesHousingMaterial->cost_per_unit;
        $steelReinforcementMaterial = RawMaterial::where('id', 24)->first(); // MATERIAL ST 0001
        $steelReinforcementCost = ($this->height - 5.188 - 1) * 0.83 * $steelReinforcementMaterial->cost_per_unit;
        $ventLatchMaterial = RawMaterial::where('name', 'VW 109 ' . $firstFrameColorLetter)->first(); // MATERIAL 109
        $ventLatchCost = 3 * 0.83 * $ventLatchMaterial->cost_per_unit * 2;
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $firstFrameColorLetter)->first(); // MATERIAL STS 0001 (W or B)
        $stopSashCost = 3 * 0.83 * $stopSashMaterial->cost_per_unit;
        $screwCoverMaterial = RawMaterial::where('name', 'SC 001 ' . $firstFrameColorLetter)->first(); // SC 001 (W or B)
        $screwCoverCost =  $this->getGlassHeigth() * 0.83 * $screwCoverMaterial->cost_per_unit;
        $sideSashClipMaterial = RawMaterial::where('name', 'SS 0001 ' . $firstFrameColorLetter)->first(); // MATERIAL SS 0001 (W or B)
        $sideSashClipCost = ($this->getGlassWidth() + 2.188) * 0.83 * $sideSashClipMaterial->cost_per_unit * 2;
        $settingBlockMaterial = RawMaterial::where('id', 10)->first(); // MATERIAL NE850062
        $settingBlockCost = 16 * $settingBlockMaterial->cost_per_unit;
        $weatherStripMeetRailSashMaterial = RawMaterial::where('name', 'W 22184 ' . $firstFrameColorLetter)->first(); // MATERIAL W 22184 (W or B)
        $weatherStripMeetRailSashCost = (2 * ($this->height - 5.23 - 0.15) + 2 * ($this->width - 5.188)) * 0.83 * $weatherStripMeetRailSashMaterial->cost_per_unit;
        $wheatherStripBottomMaterial = RawMaterial::where('name', 'W 22254 ' . $firstFrameColorLetter)->first(); // MATERIAL W 22254 (W or B)
        $wheatherStripBottomCost = $this->height * 0.83 * $wheatherStripBottomMaterial->cost_per_unit;
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('id', 7)->first(); // TSG 0003
        $tSlotSealGlazingBeatCost = ((4 * $this->getGlassHeigth()) + (4 * $this->getGlassWidth())) * 0.83 * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        $tSlotSealFrameBottomMaterial = RawMaterial::where('id', 19)->first(); // TSB 0001
        $tSlotSealFrameBottomCost = $this->height * 0.83 * $tSlotSealFrameBottomMaterial->cost_per_unit;
        $glazingBeatMaterial = RawMaterial::where('name', 'VW 108 ' . $firstFrameColorLetter)->first(); // MATERIAL 108
        $glazingBeatWidthCost = (8 * $this->getGlassWidth()) * 0.83 * $glazingBeatMaterial->cost_per_unit;
        $glazingBeatHeigthCost = ((2 * $this->getGlassHeigth()) + (2 * ($this->getGlassHeigth() - 0.25 - 0.15))) * 0.83 * $glazingBeatMaterial->cost_per_unit;

        // GET OTHER BILLS
        $workBill = config('custom.work_bill');
        $rentBill = config('custom.rent_bill');
        $electricityBill = config('custom.electricity_bill');
        $internetBill = config('custom.internet_bill');
        $otherBill = config('custom.other_bill');
        $screen_price_by_sqft = config('custom.screen_price_by_sqft');
        $glassMaterial = RawMaterial::where('name', $this->glassType)->first(); // MATERIAL Glass
        $glassCost = $this->getGlassHeigth() * $this->getGlassWidth() / 144 * $glassMaterial->cost_per_unit;
        $screenCost = 0;
        if ($this->screenRequired) {
          $screenCost = ($this->width / 2 - 0.25) * ($this->height - 5.5) / 144 * $screen_price_by_sqft;
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
          $glassCost +
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