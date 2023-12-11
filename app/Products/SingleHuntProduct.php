<?php

namespace App\Products;

use App\Interfaces\IProduct;
use App\Models\RawMaterial;
use App\Traits\Product;

class SingleHuntProduct implements IProduct {

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
      return ($this->height / 2) - (5.25 / 2) - 0.0625;
    }

    public function getGlassWidth() {
      return $this->width - 4.312;
    }

    public function getUnitPrice() {
        $ventJambMaterial = RawMaterial::where('id', 11)->first(); // MATERIAL 107
        $ventJambCost = ($this->getGlassHeigth() + 2.188) * 0.083 * $ventJambMaterial->cost_per_unit * 2;
        $ventBottomMaterial = RawMaterial::where('id', 12)->first(); // MATERIAL 106
        $ventBottomCost = ($this->width - 3.938) * 0.083 * $ventBottomMaterial->cost_per_unit;
        $ventTopMaterial = RawMaterial::where('id', 13)->first(); // MATERIAL 110
        $ventTopCost = ($this->width - 3.938) * 0.083 * $ventTopMaterial->cost_per_unit;
        $frameJambMaterial = RawMaterial::where('id', 2)->first(); // MATERIAL 103
        $frameJambCost = ($this->height - 1.562) * 0.083 * $frameJambMaterial->cost_per_unit * 2;
        $fixedMeetingRailMaterial = RawMaterial::where('id', 14)->first(); // MATERIAL 104
        $fixedMeetingRailCost = ($this->width - 4.188) * 0.083 * $fixedMeetingRailMaterial->cost_per_unit;
        $frameHeadMaterial = RawMaterial::where('id', 1)->first(); // MATERIAL 101
        $frameHeadCost = $this->width * 0.083 * $frameHeadMaterial->cost_per_unit;
        $frameSillMaterial = RawMaterial::where('id', 15)->first(); // MATERIAL 102
        $frameSillCost = $this->width * 0.083 * $frameSillMaterial->cost_per_unit;
        $glazingBeadMaterial = RawMaterial::where('id', 3)->first(); // MATERIAL 108
        $glazingBeadVerticalCost = ($this->getGlassHeigth() - 0.87 + 0.125) * 0.083 * $glazingBeadMaterial->cost_per_unit * 4;
        $glazingBeadHorizontalCost = ($this->getGlassWidth() + 0.1875) * 0.083 * $glazingBeadMaterial->cost_per_unit * 4;
        $ventLachMaterial = RawMaterial::where('id', 16)->first(); // MATERIAL 109
        $ventLachCost = 3 * 0.083 * $ventLachMaterial->cost_per_unit * 2;
        $balanceHolderMaterial = RawMaterial::where('id', 17)->first(); // MATERIAL 105
        $balanceHolderCost = 1 * 0.083 * $balanceHolderMaterial->cost_per_unit * 2;
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('id', 18)->first(); // TSG 0002
        $tSlotSealGlazingBeatCost = (($this->getGlassWidth() * 4) + ($this->getGlassHeigth() * 4)) * 0.083 * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        $tSlotSealFrameBottomMaterial = RawMaterial::where('id', 19)->first(); // TSB 0001
        $tSlotSealFrameBottomCost = $this->width * 0.083 * $tSlotSealFrameBottomMaterial->cost_per_unit;

        $firstFrameColorLetter = substr($this->frameColor, 0, 1);
        $weatherStripMeetRailSashMaterial = RawMaterial::where('name', 'W 22184 ' . $firstFrameColorLetter)->first(); // W 22184 W or B
        $weatherStripMeetRailSashCost = ((2 * ($this->getGlassHeigth() + 2.188)) + 2 * ($this->width - 3/938)) * 0.083 * $weatherStripMeetRailSashMaterial->cost_per_unit;
        $weatherStripBottomMaterial = RawMaterial::where('name', 'W 22254 ' . $firstFrameColorLetter)->first(); // W 22254 B or W
        $weatherStripBottomCost = $this->width * 0.083 * $weatherStripBottomMaterial->cost_per_unit;
        $steelReiceformentMaterial = RawMaterial::where('id', 24)->first(); // ST 0001
        $steelReiceformentCost = ($this->width - 4.188 - 1) * 0.083 * $steelReiceformentMaterial->cost_per_unit;
        $screwCoverMaterial = RawMaterial::where('name', 'SC 001 ' . $firstFrameColorLetter)->first(); // SC 001 (W or B)
        $screwCoverCost =  $this->getGlassWidth() * 0.083 * $screwCoverMaterial->cost_per_unit;
        $sideSashClipMaterial = RawMaterial::where('name', 'SS 0001 ' . $firstFrameColorLetter)->first(); // SS 0001 (W or B)
        $sideSashClipCost = ($this->getGlassHeigth() + 2.188) * 0.083 * $sideSashClipMaterial->cost_per_unit * 2;
        $settingBlockMaterial = RawMaterial::where('id', 10)->first(); // MATERIAL NE850062
        $settingBlockCost = 16 * $settingBlockMaterial->cost_per_unit;
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $firstFrameColorLetter)->first(); // STS 0001 (W or B)
        $stopSashCost = 2 * 3 * 0.083 * $stopSashMaterial->cost_per_unit;
        // GET OTHER BILLS
        $workBill = config('custom.work_bill');
        $rentBill = config('custom.rent_bill');
        $electricityBill = config('custom.electricity_bill');
        $internetBill = config('custom.internet_bill');
        $otherBill = config('custom.other_bill');
        $screen_price_by_sqft = config('custom.screen_price_by_sqft');

        $glassMaterial = RawMaterial::where('name', $this->glassType)->first(); // MATERIAL Glass
        $glassCost = $this->getGlassHeigth() * $this->getGlassWidth() / 144 * $glassMaterial->cost_per_unit;
        $balancePrice = 13.6;
        $screenCost = 0;
        if ($this->screenRequired) {
          $screenCost = ($this->getGlassWidth() - 4.687) * ($this->height / 2 - 0.375) / 144 * $screen_price_by_sqft;
        }

        $unitPriceCost = 
          $ventJambCost +
          $ventBottomCost +
          $ventTopCost +
          $frameJambCost +
          $fixedMeetingRailCost +
          $frameHeadCost +
          $frameSillCost +
          $glazingBeadVerticalCost +
          $glazingBeadHorizontalCost +
          $ventLachCost +
          $balanceHolderCost +
          $tSlotSealGlazingBeatCost +
          $tSlotSealFrameBottomCost +
          $weatherStripMeetRailSashCost +
          $weatherStripBottomCost +
          $steelReiceformentCost +
          $screwCoverCost +
          $sideSashClipCost +
          $settingBlockCost +
          $stopSashCost +
          $glassCost +
          $balancePrice +
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