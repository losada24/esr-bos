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

    public function getJamb() {
      return $this->getGlassHeigth() + 2.188;
    }

    public function getVentTopAndBottom() {
      return $this->width - 3.938;
    }

    public function getFrameJamb() {
      return $this->height - 1.562;
    }

    public function getFixedMeetingRail() {
      return $this->width - 4.188;
    }

    public function getGlazingBeatVertical() {
      return $this->getGlassHeigth() - 0.87 + 0.125;
    }

    public function getGlazingBeatHorizontal() {
      return $this->getGlassWidth() + 0.1875;
    }

    public function getSteelReiceforment() {
      return $this->getFixedMeetingRail() - 1;
    }

    public function getScreenWidth() {
      return $this->width - 4.687;
    }

    public function getScreenHeigth() {
      return $this->height / 2 - 0.375;
    }

    public function getTSlotSealGlazingBeat() {
      return ($this->getGlassWidth() * 4) + ($this->getGlassHeigth() * 4);
    }

    public function getWeatherStripMeetRailSash() {
      return (2 * $this->getJamb()) + $this->getVentTopAndBottom();
    }

    public function getBalancesBySize() {
      $singleHuntBalanceInformationPath = realpath(dirname(__FILE__) . '/../../resources/files/SingleHuntBalanceInformation.csv');
      
      $balanceInfo = [];
      if (($file = fopen($singleHuntBalanceInformationPath, 'r')) !== FALSE) {
        while (($data = fgetcsv($file)) !== FALSE) {
          $widthRange = explode('-', $data[0]);
          $heightRange = explode('-', $data[1]);
          if ($this->width >= $widthRange[0] && $this->width <= $widthRange[1] && $this->height >= $heightRange[0] && $this->height <= $heightRange[1]) {
            $balanceInfo = $data;
            break;
          }
        }
        fclose($file);
      }

      return $balanceInfo;
    }

    public function getCuttingList($qty) {
      $cuttingListResult = [];
      $materialColor = $this->getMaterialColor($this->frameColor);
      $cuttingListResult[] = $this->getCuttingListObject('Glass', $this->glassType, $qty, $this->getGlassWidth() . 'x' . $this->getGlassHeigth());
      $cuttingListResult[] = $this->getCuttingListObject('Jamb', 'VW 107 ' . $materialColor, 2 * $qty, $this->getJamb());
      $cuttingListResult[] = $this->getCuttingListObject('Vent Bottom', 'VW 106 ' . $materialColor, $qty, $this->getVentTopAndBottom());
      $cuttingListResult[] = $this->getCuttingListObject('Vent Top', 'VW 110 ' . $materialColor, $qty, $this->getVentTopAndBottom());
      $cuttingListResult[] = $this->getCuttingListObject('Frame Jamb', 'VW 103 ' . $materialColor, 2 * $qty, $this->getFrameJamb());
      $cuttingListResult[] = $this->getCuttingListObject('Punch M.R', ' - ', 1, $this->getGlassHeigth() + 0.44);
      $cuttingListResult[] = $this->getCuttingListObject('Fixed Meeting Rail', 'VW 104 ' . $materialColor, $qty, $this->getFixedMeetingRail());
      $cuttingListResult[] = $this->getCuttingListObject('Frame Head', 'VW 101 ' . $materialColor, $qty, $this->width);
      $cuttingListResult[] = $this->getCuttingListObject('Frame Sill', 'VW 102 ' . $materialColor, $qty, $this->width);
      $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Vertical', 'VW 108 ' . $materialColor, 4 * $qty, $this->getGlazingBeatVertical());
      $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Horizontal', 'VW 108 ' . $materialColor, 4 * $qty, $this->getGlazingBeatHorizontal());
      $cuttingListResult[] = $this->getCuttingListObject('Steel Reiceforcement square', 'ST 0001', $qty, $this->getScreenWidth() . 'x' . $this->getScreenHeigth());
      $cuttingListResult[] = $this->getCuttingListObject('Vent Latch', 'VW 109 ' . $materialColor, 2 * $qty, '3 inch');
      $cuttingListResult[] = $this->getCuttingListObject('Balance Holder', 'VW 105 MF', 2 * $qty, '1 inch');
      $cuttingListResult[] = $this->getCuttingListObject('Stop Sash', 'STS 0001 ' . $materialColor, 2 * $qty, '3 inch');
      $cuttingListResult[] = $this->getCuttingListObject('T Slot Seal Glazing Beat 5/16', 'TSG 0002', $qty, $this->getTSlotSealGlazingBeat());
      $cuttingListResult[] = $this->getCuttingListObject('T Slot Seal Frame Bottom', 'TSB 0001', $qty, $this->width);
      $cuttingListResult[] = $this->getCuttingListObject('Weather Strip Meet Rail Sash', 'W 22184 ' . $materialColor, $qty, $this->getWeatherStripMeetRailSash());
      $cuttingListResult[] = $this->getCuttingListObject('Weather Strip Bottom', 'W 22254 BL', $qty, $this->width);
      $cuttingListResult[] = $this->getCuttingListObject('Screw Cover', 'SC 0001 ' . $materialColor, $qty, $this->getGlassWidth());
      $cuttingListResult[] = $this->getCuttingListObject('Side Sash Clip', 'SS 0001 ' . $materialColor, 2 * $qty, $this->getJamb());
      $cuttingListResult[] = $this->getCuttingListObject('Setting Block', 'NE 850125', 16 * $qty, '');
      $balanceData = $this->getBalancesBySize();
      //SILICONE
      if (!empty($balanceData)) {
        $cuttingListResult[] = $this->getCuttingListObject('Balance', $balanceData[3], 2, $balanceData[2]);
      }

      if ($this->screenRequired) {
        $cuttingListResult[] = $this->getCuttingListObject('Screen', '', $qty, $this->getScreenWidth() . 'x' . $this->getScreenHeigth());
      }

      return $cuttingListResult;
    }

    public function getUnitPrice() {
        $firstFrameColorLetter = $this->getMaterialColor($this->frameColor);
        $ventJambMaterial = RawMaterial::where('name', 'VW 107 ' . $firstFrameColorLetter)->first(); // MATERIAL 107
        $ventJambCost = $this->getJamb() * 0.083 * $ventJambMaterial->cost_per_unit * 2;
        $ventBottomMaterial = RawMaterial::where('name', 'VW 106 ' . $firstFrameColorLetter)->first(); // MATERIAL 106
        $ventBottomCost = $this->getVentTopAndBottom() * 0.083 * $ventBottomMaterial->cost_per_unit;
        $ventTopMaterial = RawMaterial::where('name', 'VW 110 ' . $firstFrameColorLetter)->first(); // MATERIAL 110
        $ventTopCost = $this->getVentTopAndBottom() * 0.083 * $ventTopMaterial->cost_per_unit;
        $frameJambMaterial = RawMaterial::where('name', 'VW 103 ' . $firstFrameColorLetter)->first(); // MATERIAL 103
        $frameJambCost = $this->getFrameJamb() * 0.083 * $frameJambMaterial->cost_per_unit * 2;
        $fixedMeetingRailMaterial = RawMaterial::where('name', 'VW 104 ' . $firstFrameColorLetter)->first(); // MATERIAL 104
        $fixedMeetingRailCost = $this->getFixedMeetingRail() * 0.083 * $fixedMeetingRailMaterial->cost_per_unit;
        $frameHeadMaterial = RawMaterial::where('name', 'VW 101 ' . $firstFrameColorLetter)->first(); // MATERIAL 101
        $frameHeadCost = $this->width * 0.083 * $frameHeadMaterial->cost_per_unit;
        $frameSillMaterial = RawMaterial::where('name', 'VW 102 ' . $firstFrameColorLetter)->first(); // MATERIAL 102
        $frameSillCost = $this->width * 0.083 * $frameSillMaterial->cost_per_unit;
        $glazingBeadMaterial = RawMaterial::where('name', 'VW 108 ' . $firstFrameColorLetter)->first(); // MATERIAL 108
        $glazingBeadVerticalCost = $this->getGlazingBeatVertical() * 0.083 * $glazingBeadMaterial->cost_per_unit * 4;
        $glazingBeadHorizontalCost = $this->getGlazingBeatHorizontal() * 0.083 * $glazingBeadMaterial->cost_per_unit * 4;
        $ventLachMaterial = RawMaterial::where('name', 'VW 103 ' . $firstFrameColorLetter)->first(); // MATERIAL 109
        $ventLachCost = 3 * 0.083 * $ventLachMaterial->cost_per_unit * 2;
        $balanceHolderMaterial = RawMaterial::where('name', 'VW 105 MF')->first(); // MATERIAL VW 105 MF
        $balanceHolderCost = 1 * 0.083 * $balanceHolderMaterial->cost_per_unit * 2;
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('name', 'TSG 0002')->first(); // TSG 0002
        $tSlotSealGlazingBeatCost = $this->getTSlotSealGlazingBeat() * 0.083 * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        $tSlotSealFrameBottomMaterial = RawMaterial::where('name', 'TSB 0001')->first(); // TSB 0001
        $tSlotSealFrameBottomCost = $this->width * 0.083 * $tSlotSealFrameBottomMaterial->cost_per_unit;
        $weatherStripMeetRailSashMaterial = RawMaterial::where('name', 'W 22184 ' . $firstFrameColorLetter)->first(); // W 22184 W or B
        $weatherStripMeetRailSashCost = $this->getWeatherStripMeetRailSash() * 0.083 * $weatherStripMeetRailSashMaterial->cost_per_unit;
        $weatherStripBottomMaterial = RawMaterial::where('name', 'W 22254 BL')->first(); // W 22254 BL
        $weatherStripBottomCost = $this->width * 0.083 * $weatherStripBottomMaterial->cost_per_unit;
        $steelReiceformentMaterial = RawMaterial::where('name', 'ST 0001')->first(); // ST 0001
        $steelReiceformentCost = $this->getSteelReiceforment() * 0.083 * $steelReiceformentMaterial->cost_per_unit;
        $screwCoverMaterial = RawMaterial::where('name', 'SC 0001 ' . $firstFrameColorLetter)->first(); // SC 001 (W or B)
        $screwCoverCost =  $this->getGlassWidth() * 0.083 * $screwCoverMaterial->cost_per_unit;
        $sideSashClipMaterial = RawMaterial::where('name', 'SS 0001 ' . $firstFrameColorLetter)->first(); // SS 0001 (W or B)
        $sideSashClipCost = $this->getJamb() * 0.083 * $sideSashClipMaterial->cost_per_unit * 2;
        $settingBlockMaterial = RawMaterial::where('name', 'NE 850125')->first(); // MATERIAL NE 850062
        $settingBlockCost = 16 * $settingBlockMaterial->cost_per_unit;
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $firstFrameColorLetter)->first(); // STS 0001 (W or B)
        $stopSashCost = 2 * 3 * 0.083 * $stopSashMaterial->cost_per_unit;
        $structuralSliconeMaterial = RawMaterial::where('id', 32)->first(); //STRUCTURAL SILICONE
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
        $balancePrice = 13.6;
        $screenCost = 0;
        if ($this->screenRequired) {
          $screenCost = $this->getScreenWidth() * $this->getScreenHeigth() / 144 * $screen_price_by_sqft;
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
          $structuralSliconeMaterialCost +
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