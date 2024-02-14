<?php

namespace App\Products;

use App\Enum\FrameColorEnum;
use App\Interfaces\IProduct;
use App\Models\RawMaterial;
use App\Traits\Fractions;
use App\Traits\Product;

class SingleHuntProduct implements IProduct {

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
      return round(($this->height / 2) - (5.25 / 2) - 0.0625, 3);
    }

    public function getGlassWidth() {
      return round($this->width - 4.312, 3);
    }

    public function getJamb() {
      return round($this->getGlassHeigth() + 2.188, 3);
    }

    public function getVentTopAndBottom() {
      return round($this->width - 3.938, 3);
    }

    public function getFrameJamb() {
      return round($this->height - 1.562, 3);
    }

    public function getFixedMeetingRail() {
      return round($this->width - 4.188, 3);
    }

    public function getGlazingBeatVertical() {
      return round($this->getGlassHeigth() - 0.87 + 0.125, 3);
    }

    public function getGlazingBeatHorizontal() {
      return round($this->getGlassWidth() + 0.1875, 3);
    }

    public function getSteelReiceforment() {
      return round($this->getFixedMeetingRail() - 1, 3);
    }

    public function getScreenWidth() {
      return round($this->width - 4.687 - 0.125, 3);
    }

    public function getScreenHeigth() {
      return round($this->height / 2 - 0.375, 3);
    }

    public function getTSlotSealGlazingBeat() {
      return  round(($this->getGlassWidth() * 4) + ($this->getGlassHeigth() * 4), 3);
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

    public function getMaterialConsumption($qty) {
      $vw107 = RawMaterial::where('name', 'VW 107 ' . $this->materialColor)->first();
      $vw106 = RawMaterial::where('name', 'VW 106 ' . $this->materialColor)->first();
      $vw110 = RawMaterial::where('name', 'VW 110 ' . $this->materialColor)->first();
      $vw103 = RawMaterial::where('name', 'VW 103 ' . $this->materialColor)->first();
      $vw104 = RawMaterial::where('name', 'VW 104 ' . $this->materialColor)->first();
      $vw101 = RawMaterial::where('name', 'VW 101 ' . $this->materialColor)->first();
      $vw102 = RawMaterial::where('name', 'VW 102 ' . $this->materialColor)->first();
      $vw108 = RawMaterial::where('name', 'VW 108 ' . $this->materialColor)->first();
      $st0001 = RawMaterial::where('name', 'ST 0001')->first();
      $vw109 = RawMaterial::where('name', 'VW 109 ' . $this->materialColor)->first();
      $vm105MF = RawMaterial::where('name', 'VW 105 MF')->first();
      $sts0001 = RawMaterial::where('name', 'STS 0001 ' . $this->materialColor)->first();
      $stg0002 = RawMaterial::where('name', 'TSG 0002')->first();
      $tbs0001 = RawMaterial::where('name', 'TSB 0001')->first();
      if ($this->frameColor == FrameColorEnum::$FRAME_COLOR["WHITE"]) {
        $weatherStripMeetRailSash = RawMaterial::where('name', 'W 22174 W')->first(); // W 22184 W or B
      } else {
        $weatherStripMeetRailSash = RawMaterial::where('name', 'W 22174 BL')->first(); // W 22184 W or B
      }
      //$w22184 = RawMaterial::where('name', 'W 22184 ' . $this->materialColor)->first();
      $w22254BL = RawMaterial::where('name', 'W 22254 BL')->first();
      $sc0001 = RawMaterial::where('name', 'SC 0001 ' . $this->materialColor)->first();
      $ss0001 = RawMaterial::where('name', 'SS 0001 ' . $this->materialColor)->first();
      $ne850125 = RawMaterial::where('name', 'NE 850125')->first();
      $ppa081 = RawMaterial::where('name', 'PPA 08-1')->first();
      $ls0001 = RawMaterial::where('name', 'LS 0001')->first(); // MATERIAL LS 0001
      $clb0001 = RawMaterial::where('name', 'CLB 0001')->first(); // MATERIAL CLB 0001

      return [
        'VW 107 ' . $this->materialColor => [
          'amount' => $this->getJamb() * 0.083 * 2 * $qty,
          'unit_of_measurement' => $vw107->unit_of_measurement,
          'storage_measure' => $vw107->storage_measure,
          'notes' => $vw107->notes
        ],
        'VW 106 ' . $this->materialColor => [
          'amount' => $this->getVentTopAndBottom() * 0.083 * $qty,
          'unit_of_measurement' => $vw106->unit_of_measurement,
          'storage_measure' => $vw106->storage_measure,
          'notes' => $vw106->notes
        ],
        'VW 110 ' . $this->materialColor => [
          'amount' => $this->getVentTopAndBottom() * 0.083 * $qty,
          'unit_of_measurement' => $vw110->unit_of_measurement,
          'storage_measure' => $vw110->storage_measure,
          'notes' => $vw110->notes
        ],
        'VW 103 ' . $this->materialColor => [
          'amount' => $this->getFrameJamb() * 0.083 * 2 * $qty,
          'unit_of_measurement' => $vw103->unit_of_measurement,
          'storage_measure' => $vw103->storage_measure,
          'notes' => $vw103->notes
        ],
        'VW 104 ' . $this->materialColor => [
          'amount' => $this->getFixedMeetingRail() * 0.083 * $qty,
          'unit_of_measurement' => $vw104->unit_of_measurement,
          'storage_measure' => $vw104->storage_measure,
          'notes' => $vw104->notes
        ],
        'VW 101 ' . $this->materialColor => [
          'amount' => $this->width * 0.083 * $qty,
          'unit_of_measurement' => $vw101->unit_of_measurement,
          'storage_measure' => $vw101->storage_measure,
          'notes' => $vw101->notes
        ],
        'VW 102 ' . $this->materialColor => [
          'amount' => $this->width * 0.083 * $qty,
          'unit_of_measurement' => $vw102->unit_of_measurement,
          'storage_measure' => $vw102->storage_measure,
          'notes' => $vw106->notes
        ],
        'VW 108 ' . $this->materialColor => [
          'amount' => ($this->getGlazingBeatVertical() * 0.083 * 4 * $qty) + ($this->getGlazingBeatHorizontal() * 0.083 * 4 * $qty),
          'unit_of_measurement' => $vw108->unit_of_measurement,
          'storage_measure' => $vw108->storage_measure,
          'notes' => $vw108->notes
        ],
        'ST 0001' => [
          'amount' => $this->getSteelReiceforment() * 0.083 * $qty,
          'unit_of_measurement' => $st0001->unit_of_measurement,
          'storage_measure' => $st0001->storage_measure,
          'notes' => $st0001->notes
        ],
        'VW 109 ' . $this->materialColor => [
          'amount' => 2 * $qty,
          'unit_of_measurement' => $vw109->unit_of_measurement,
          'storage_measure' => $vw109->storage_measure,
          'notes' => $vw109->notes
        ],
        'VW 105 MF' => [
          'amount' => 2 * $qty,
          'unit_of_measurement' => $vm105MF->unit_of_measurement,
          'storage_measure' => $vm105MF->storage_measure,
          'notes' => $vm105MF->notes
        ],
        'STS 0001 ' . $this->materialColor => [
          'amount' => 2 * $qty,
          'unit_of_measurement' => $sts0001->unit_of_measurement,
          'storage_measure' => $sts0001->storage_measure,
          'notes' => $sts0001->notes
        ],
        'TSG 0002' => [
          'amount' => $this->getTSlotSealGlazingBeat() * 0.083 * $qty,
          'unit_of_measurement' => $stg0002->unit_of_measurement,
          'storage_measure' => $stg0002->storage_measure,
          'notes' => $stg0002->notes
        ],
        'TSB 0001' => [
          'amount' => $this->width * 0.083 * $qty,
          'unit_of_measurement' => $tbs0001->unit_of_measurement,
          'storage_measure' => $tbs0001->storage_measure,
          'notes' => $tbs0001->notes
        ],
        $weatherStripMeetRailSash->name => [
          'amount' => $this->getWeatherStripMeetRailSash() * 0.083 * $qty,
          'unit_of_measurement' => $weatherStripMeetRailSash->unit_of_measurement,
          'storage_measure' => $weatherStripMeetRailSash->storage_measure,
          'notes' => $weatherStripMeetRailSash->notes
        ],
        'W 22254 BL' => [
          'amount' => $this->width * 0.083 * $qty,
          'unit_of_measurement' => $w22254BL->unit_of_measurement,
          'storage_measure' => $w22254BL->storage_measure,
          'notes' => $w22254BL->notes
        ],
        'SC 0001 ' . $this->materialColor => [
          'amount' => $this->getGlassWidth() * 0.083 * $qty,
          'unit_of_measurement' => $sc0001->unit_of_measurement,
          'storage_measure' => $sc0001->storage_measure,
          'notes' => $sc0001->notes
        ],
        'SS 0001 ' . $this->materialColor => [
          'amount' => $this->getJamb() * 0.083 * 2 * $qty,
          'unit_of_measurement' => $ss0001->unit_of_measurement,
          'storage_measure' => $ss0001->storage_measure,
          'notes' => $ss0001->notes
        ],
        'NE 850125' => [
          'amount' => 16 * $qty,
          'unit_of_measurement' => $ne850125->unit_of_measurement,
          'storage_measure' => $ne850125->storage_measure,
          'notes' => $ne850125->notes
        ],
        'PPA 08-1' => [
          'amount' => 16 * $qty,
          'unit_of_measurement' => $ppa081->unit_of_measurement,
          'storage_measure' => $ppa081->storage_measure,
          'notes' => $ppa081->notes
        ],
        'LS 0001' => [
          'amount' => 2 * $qty,
          'unit_of_measurement' => $ls0001->unit_of_measurement,
          'storage_measure' => $ls0001->storage_measure,
          'notes' => $ls0001->notes
        ],
        'CLB 0001' => [
          'amount' => 2 * $qty,
          'unit_of_measurement' => $clb0001->unit_of_measurement,
          'storage_measure' => $clb0001->storage_measure,
          'notes' => $clb0001->notes
        ],
      ];
    }

    public function getCuttingList($qty) {
      $cuttingListResult = [];
      $cuttingListResult[] = $this->getCuttingListObject('Frame Head', 'VW 101 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->width), $this->width);
      $cuttingListResult[] = $this->getCuttingListObject('Frame Jamb', 'VW 103 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getFrameJamb()), $this->getFrameJamb());
      $cuttingListResult[] = $this->getCuttingListObject('Frame Sill', 'VW 102 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->width), $this->width);
      $cuttingListResult[] = $this->getCuttingListObject('Meeting Rail', 'VW 104 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getFixedMeetingRail()), $this->getFixedMeetingRail());
      $cuttingListResult[] = $this->getCuttingListObject('Vent Jamb', 'VW 107 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getJamb()), $this->getJamb());
      $cuttingListResult[] = $this->getCuttingListObject('Vent Bottom', 'VW 106 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getVentTopAndBottom()), $this->getVentTopAndBottom());
      $cuttingListResult[] = $this->getCuttingListObject('Vent Top', 'VW 110 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getVentTopAndBottom()), $this->getVentTopAndBottom());
      $cuttingListResult[] = $this->getCuttingListObject('Punch M.R', ' - ', '-', $this->getNumberWithFraction($this->getGlassHeigth() + 0.44), 0);
      $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Vertical', 'VW 108 ' . $this->materialColor, 4 * $qty, $this->getNumberWithFraction($this->getGlazingBeatVertical()), $this->getGlazingBeatVertical());
      $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Horizontal', 'VW 108 ' . $this->materialColor, 4 * $qty, $this->getNumberWithFraction($this->getGlazingBeatHorizontal()), $this->getGlazingBeatHorizontal());
      $cuttingListResult[] = $this->getCuttingListObject('Steel Reiceforcement square', 'ST 0001', $qty, $this->getNumberWithFraction($this->getSteelReiceforment()), $this->getSteelReiceforment());
      $cuttingListResult[] = $this->getCuttingListObject('Screw Cover', 'SC 0001 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getGlassWidth()), $this->getGlassWidth());
      $cuttingListResult[] = $this->getCuttingListObject('Side Sash Clip', 'SS 0001 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getJamb()), $this->getJamb());
      
      $balanceData = $this->getBalancesBySize();
      //SILICONE
      if (!empty($balanceData)) {
        $cuttingListResult[] = $this->getCuttingListObject('Balance', $balanceData[3], 2, $balanceData[2], 0);
      }
      
      if ($this->screenRequired) {
        $cuttingListResult[] = $this->getCuttingListObject('Screen', '', $qty, $this->getNumberWithFraction($this->getScreenWidth()) . ' x ' . $this->getNumberWithFraction($this->getScreenHeigth()), 0);
      }
      $cuttingListResult[] = $this->getCuttingListObject('Glass', $this->glassType, 2 * $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getGlassHeigth()), 0);
      
      return $cuttingListResult;
    }

    //TODO: Refactor to use in getCuttingList
    public function getPoScreen($qty) {
      $cuttingListResult = [];
      $cuttingListResult[] = $this->getCuttingListObject('Screen', '', $qty, $this->getNumberWithFraction($this->getScreenWidth()) . ' x ' . $this->getNumberWithFraction($this->getScreenHeigth()));
      
      return $cuttingListResult;
    }

    //TODO: Refactor to use in getCuttingList
    public function getPoGlass($qty) {
      $cuttingListResult = [];
      $cuttingListResult[] = $this->getCuttingListObject('Glass', $this->glassType, 2 * $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getGlassHeigth()));

      return $cuttingListResult;
    }

    public function getUnitPrice() {
        $ventJambMaterial = RawMaterial::where('name', 'VW 107 ' . $this->materialColor)->first(); // MATERIAL 107
        $ventJambCost = $this->getJamb() * 0.083 * $ventJambMaterial->cost_per_unit * 2;
        $ventBottomMaterial = RawMaterial::where('name', 'VW 106 ' . $this->materialColor)->first(); // MATERIAL 106
        $ventBottomCost = $this->getVentTopAndBottom() * 0.083 * $ventBottomMaterial->cost_per_unit;
        $ventTopMaterial = RawMaterial::where('name', 'VW 110 ' . $this->materialColor)->first(); // MATERIAL 110
        $ventTopCost = $this->getVentTopAndBottom() * 0.083 * $ventTopMaterial->cost_per_unit;
        $frameJambMaterial = RawMaterial::where('name', 'VW 103 ' . $this->materialColor)->first(); // MATERIAL 103
        $frameJambCost = $this->getFrameJamb() * 0.083 * $frameJambMaterial->cost_per_unit * 2;
        $fixedMeetingRailMaterial = RawMaterial::where('name', 'VW 104 ' . $this->materialColor)->first(); // MATERIAL 104
        $fixedMeetingRailCost = $this->getFixedMeetingRail() * 0.083 * $fixedMeetingRailMaterial->cost_per_unit;
        $frameHeadMaterial = RawMaterial::where('name', 'VW 101 ' . $this->materialColor)->first(); // MATERIAL 101
        $frameHeadCost = $this->width * 0.083 * $frameHeadMaterial->cost_per_unit;
        $frameSillMaterial = RawMaterial::where('name', 'VW 102 ' . $this->materialColor)->first(); // MATERIAL 102
        $frameSillCost = $this->width * 0.083 * $frameSillMaterial->cost_per_unit;
        $glazingBeadMaterial = RawMaterial::where('name', 'VW 108 ' . $this->materialColor)->first(); // MATERIAL 108
        $glazingBeadVerticalCost = $this->getGlazingBeatVertical() * 0.083 * $glazingBeadMaterial->cost_per_unit * 4;
        $glazingBeadHorizontalCost = $this->getGlazingBeatHorizontal() * 0.083 * $glazingBeadMaterial->cost_per_unit * 4;
        $ventLachMaterial = RawMaterial::where('name', 'VW 109 ' . $this->materialColor)->first(); // MATERIAL 109
        $ventLachCost = 3 * 0.083 * $ventLachMaterial->cost_per_unit * 2;
        $balanceHolderMaterial = RawMaterial::where('name', 'VW 105 MF')->first(); // MATERIAL VW 105 MF
        $balanceHolderCost = 1 * 0.083 * $balanceHolderMaterial->cost_per_unit * 2;
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('name', 'TSG 0002')->first(); // TSG 0002
        $tSlotSealGlazingBeatCost = $this->getTSlotSealGlazingBeat() * 0.083 * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        $tSlotSealFrameBottomMaterial = RawMaterial::where('name', 'TSB 0001')->first(); // TSB 0001
        $tSlotSealFrameBottomCost = $this->width * 0.083 * $tSlotSealFrameBottomMaterial->cost_per_unit;
        if ($this->frameColor == FrameColorEnum::$FRAME_COLOR["WHITE"]) {
          $weatherStripMeetRailSashMaterial = RawMaterial::where('name', 'W 22174 W')->first(); // W 22184 W or B
        } else {
          $weatherStripMeetRailSashMaterial = RawMaterial::where('name', 'W 22174 BL')->first(); // W 22184 W or B
        }
        $weatherStripMeetRailSashCost = $this->getWeatherStripMeetRailSash() * 0.083 * $weatherStripMeetRailSashMaterial->cost_per_unit;
        $weatherStripBottomMaterial = RawMaterial::where('name', 'W 22254 BL')->first(); // W 22254 BL
        $weatherStripBottomCost = $this->width * 0.083 * $weatherStripBottomMaterial->cost_per_unit;
        $steelReiceformentMaterial = RawMaterial::where('name', 'ST 0001')->first(); // ST 0001
        $steelReiceformentCost = $this->getSteelReiceforment() * 0.083 * $steelReiceformentMaterial->cost_per_unit;
        $screwCoverMaterial = RawMaterial::where('name', 'SC 0001 ' . $this->materialColor)->first(); // SC 001 (W or B)
        $screwCoverCost =  $this->getGlassWidth() * 0.083 * $screwCoverMaterial->cost_per_unit;
        $sideSashClipMaterial = RawMaterial::where('name', 'SS 0001 ' . $this->materialColor)->first(); // SS 0001 (W or B)
        $sideSashClipCost = $this->getJamb() * 0.083 * $sideSashClipMaterial->cost_per_unit * 2;
        $settingBlockMaterial = RawMaterial::where('name', 'NE 850125')->first(); // MATERIAL NE 850062
        $settingBlockCost = 16 * $settingBlockMaterial->cost_per_unit;
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $this->materialColor)->first(); // STS 0001 (W or B)
        $stopSashCost = 2 * 3 * 0.083 * $stopSashMaterial->cost_per_unit;
        $structuralSliconeMaterial = RawMaterial::where('id', 32)->first(); //STRUCTURAL SILICONE
        $structuralSliconeMaterialCost = (($this->getGlassWidth() * 4) + ($this->getGlassHeigth() * 4)) * 0.083 * $structuralSliconeMaterial->cost_per_unit;
        $screwMaterial = RawMaterial::where('name', 'PPA 08-1')->first(); // MATERIAL Screws 8x1
        $screwMaterialCost = $screwMaterial->cost_per_unit * 16;
        $lockSprignMaterial = RawMaterial::where('name', 'LS 0001')->first(); // MATERIAL LS 0001
        $lockSprignMaterialCost = $lockSprignMaterial->cost_per_unit * 2;
        $clipTakeOffBalanceMaterial = RawMaterial::where('name', 'CLB 0001')->first(); // MATERIAL CLB 0001
        $clipTakeOffBalanceCost = $clipTakeOffBalanceMaterial->cost_per_unit * 2;

        // GET OTHER BILLS
        $workBill = config('custom.work_bill');
        $rentBill = config('custom.rent_bill');
        $electricityBill = config('custom.electricity_bill');
        $internetBill = config('custom.internet_bill');
        $otherBill = config('custom.other_bill');
        $screen_price_by_sqft = config('custom.screen_price_by_sqft');
        $packing = config('custom.packing');
        $cornerSilicone = config('custom.corner_silicone');

        $glassMaterial = RawMaterial::where('name', $this->glassType)->first(); // MATERIAL Glass
        $glassCost = $this->getGlassSize($this->getGlassHeigth()) * $this->getGlassSize($this->getGlassWidth()) / 144 * $glassMaterial->cost_per_unit;
        $balancePrice = 13.6;
        $screenCost = 0;
        if ($this->screenRequired) {
          $screenCost = $this->getScreenWidth() * $this->getScreenHeigth() / 144 * $screen_price_by_sqft;
        }

        /* echo "ventJambCost:" . $ventJambCost . "<br>" ;
            echo  "ventBottomCost: " . $ventBottomCost . "<br>" ;
            echo  "ventTopCost: " . $ventTopCost . "<br>" ;
            echo  "frameJambCost:" . $frameJambCost . "<br>" ;
            echo  "fixedMeetingRailCost: " . $fixedMeetingRailCost . "<br>" ;
            echo  "frameHeadCost: " . $frameHeadCost . "<br>" ;
            echo  "frameSillCost: " . $frameSillCost . "<br>" ;
            echo  "glazingBeadVerticalCost: " . $glazingBeadVerticalCost . "<br>" ;
            echo  "glazingBeadHorizontalCost: " . $glazingBeadHorizontalCost . "<br>" ;
            echo  "ventLachCost: " . $ventLachCost . "<br>" ;
            echo  "balanceHolderCost: " . $balanceHolderCost . "<br>" ;
            echo  "tSlotSealGlazingBeatCost: " . $tSlotSealGlazingBeatCost . "<br>" ;
            echo  "tSlotSealFrameBottomCost: " . $tSlotSealFrameBottomCost . "<br>" ;
            echo  "weatherStripMeetRailSashCost: " . $weatherStripMeetRailSashCost . "<br>" ;
            echo  "weatherStripBottomCost: " . $weatherStripBottomCost . "<br>" ;
            echo  "steelReiceformentCost: " . $steelReiceformentCost . "<br>" ;
            echo  "screwCoverCost: " . $screwCoverCost . "<br>" ;
            echo  "sideSashClipCost: " . $sideSashClipCost . "<br>" ;
            echo  "settingBlockCost: " . $settingBlockCost . "<br>" ;
            echo  "stopSashCost: " . $stopSashCost . "<br>" ;
            echo  "glassCost: " . ($glassCost * 2) . "<br>" ;
            echo  "balancePrice: " . $balancePrice . "<br>" ;
            echo  "structuralSliconeMaterialCost: " . $structuralSliconeMaterialCost . "<br>" ;
            echo  "screenCost: " . $screenCost . "<br>" ;
            echo  "packing: " . $packing . "<br>" ;
            echo  "Screws: " . $screwMaterialCost . "<br>" ;
            echo  "LockSpringCost: " . $lockSprignMaterialCost . "<br>" ;
            echo  "clipTakeOffBalanceCost: " . $clipTakeOffBalanceCost . "<br>" ;
            echo "Total:" . round($ventJambCost +
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
                        ($glassCost * 2) +
                        $balancePrice +
                        $structuralSliconeMaterialCost +
                        $screenCost +
                        $screwMaterialCost +
                        $lockSprignMaterialCost +
                        $packing, 2) . "<br>";
        die;*/

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
          ($glassCost * 2) +
          $balancePrice +
          $structuralSliconeMaterialCost +
          $screwMaterialCost +
          $lockSprignMaterialCost +
          $clipTakeOffBalanceCost +
          $screenCost +
          $packing +
          $workBill +
          $rentBill +
          $electricityBill +
          $internetBill +
          $cornerSilicone +
          $otherBill;

        //GET COMPANY MOCKUP
        /*$companyMockup = $this->getCompanyMockup();
        $companyMockupCost = $unitPriceCost * $companyMockup / 100;

        $promotion = $this->getCompanyPromotion();
        $promotionCost = ($unitPriceCost + $companyMockupCost) * $promotion / 100;*/

        return $unitPriceCost;
    }
}