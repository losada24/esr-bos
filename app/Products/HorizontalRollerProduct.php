<?php

namespace App\Products;

use App\Enum\FrameColorEnum;
use App\Enum\HorizontalRollerHandleEnum;
use App\Enum\UnitOfMeasurement;
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
    public $muntinPanels;
    public $panelA;
    public $panelB;
    public $muntinPattern;
    public $muntinInteriorStyle;
    public $muntinExteriorStyle;
    public $verticalLines;
    public $horizontalLines;
    public $handle;
    
    public function __construct(
      $width, 
      $height, 
      $frameColor, 
      $glassType, 
      $screenRequired,
      $muntinPanels = false,
      $panelA = false,
      $panelB = false,
      $muntinPattern = "",
      $muntinInteriorStyle = "",
      $muntinExteriorStyle = "",
      $verticalLines = 0,
      $horizontalLines = 0,
      $handle = ""
    ) {
        $this->width = (float) $width;
        $this->height = (float) $height;
        $this->frameColor = $frameColor;
        $this->glassType = $glassType;
        $this->screenRequired = $screenRequired;
        $this->materialColor = $this->getMaterialColor($frameColor);
        $this->muntinPanels = $muntinPanels;
        $this->panelA = $panelA;
        $this->panelB = $panelB;
        $this->muntinPattern = $muntinPattern;
        $this->muntinInteriorStyle = $muntinInteriorStyle;
        $this->muntinExteriorStyle = $muntinExteriorStyle;
        $this->verticalLines = $verticalLines;
        $this->horizontalLines = $horizontalLines;
        $this->handle = $handle;
    }

    public function getGlassHeigth() {
      return round($this->height - 5.21 - 0.1, 3);
    }

    public function getGlassWidth() {
      return round(($this->width / 2) - (5.25 / 2) - 0.125, 3);
    }

    public function getMoveGlassHeight() {
      return round($this->getGlassHeigth() - 0.25 - 0.15 - 0.125, 3);
    }

    public function getVentJamb() {
      return round($this->height - 5.23 - 0.15 - 0.125, 3);
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
      return round($this->height - 5.625 - 0.125, 3);
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

    public function getScrewCover() {
      return round($this->height - 6.125, 3);
    }

    public function getMaterialRelease($qty) {
      // $rh0001 = RawMaterial::where('name', 'RH 0001')->first();
      $vh0001 = RawMaterial::where('name', 'WH 0001 ' . $this->materialColor)->first();

      $material_release = [
        'WH 0001 ' . $this->materialColor => [
          'amount' => 2 * $qty,
          'unit_of_measurement' => $vh0001->unit_of_measurement,
          'storage_measure' => $vh0001->storage_measure,
          'notes' => $vh0001->notes
        ],
      ];

      if ($this->handle == HorizontalRollerHandleEnum::$HANDLE['VENT LATCH'] || $this->handle == HorizontalRollerHandleEnum::$HANDLE['BOTH']) {
        $material_release['VENT LACH W CLIP ' . $this->materialColor] = [
          'amount' => 2 * $qty,
          'unit_of_measurement' => UnitOfMeasurement::$UNIT_OF_MEASUREMENT["UNIT"],
          'storage_measure' => 0,
          'notes' => ""
        ];
      }

      if ($this->handle == HorizontalRollerHandleEnum::$HANDLE['SWIPE LOCK'] || $this->handle == HorizontalRollerHandleEnum::$HANDLE['BOTH']) {
        $material_name = 'SLOCK 0001 W';
        if ($this->frameColor != FrameColorEnum::$FRAME_COLOR["WHITE"]) {
          $material_name = 'SLOCK 0001 BL';
        }
        $swipeLock = RawMaterial::where('name', $material_name)->first();
        $material_release[$material_name] = [
          'amount' => 2 * $qty,
          'unit_of_measurement' => UnitOfMeasurement::$UNIT_OF_MEASUREMENT["UNIT"],
          'storage_measure' => 0,
          'notes' => $swipeLock->notes
        ];
      }

      return $material_release;
    }

    public function getMaterialConsumption($qty) {
      $vw110 = RawMaterial::where('name', 'VW 110 ' . $this->materialColor)->first();
      $vw106 = RawMaterial::where('name', 'VW 106 ' . $this->materialColor)->first();
      $vw107 = RawMaterial::where('name', 'VW 107 ' . $this->materialColor)->first();
      $vw101 = RawMaterial::where('name', 'VW 101 ' . $this->materialColor)->first();
      $vw102 = RawMaterial::where('name', 'VW 102 ' . $this->materialColor)->first();
      $vw104 = RawMaterial::where('name', 'VW 104 ' . $this->materialColor)->first();
      $vw108 = RawMaterial::where('name', 'VW 108 ' . $this->materialColor)->first();
      $vw111 = RawMaterial::where('name', 'VW 111 ' . $this->materialColor)->first();
      $vw112 = RawMaterial::where('name', 'VW 112 ' . $this->materialColor)->first();
      $vw109 = RawMaterial::where('name', 'VW 109 ' . $this->materialColor)->first();
      $vh0001 = RawMaterial::where('name', 'WH 0001 ' . $this->materialColor)->first();
      $sts0001 = RawMaterial::where('name', 'STS 0001 ' . $this->materialColor)->first();
      $sc0001 = RawMaterial::where('name', 'SC 0001 ' . $this->materialColor)->first();
      $ss0001 = RawMaterial::where('name', 'SS 0001 ' . $this->materialColor)->first();
      
      if ($this->frameColor == FrameColorEnum::$FRAME_COLOR["WHITE"]) {
        $weatherStripMeetRailSash = RawMaterial::where('name', 'W 22174 W')->first(); // W 22174 W or BL
        $swipeLock = RawMaterial::where('name', 'SLOCK 0001 W')->first();
      } else {
        $weatherStripMeetRailSash = RawMaterial::where('name', 'W 22174 BL')->first(); // W 22174 W or BL
        $swipeLock = RawMaterial::where('name', 'SLOCK 0001 BL')->first();
      }
      $w22254 = RawMaterial::where('name', 'W 22254 BL')->first();
      $rh0001 = RawMaterial::where('name', 'RH 0001')->first();
      $st0001 = RawMaterial::where('name', 'ST 0001')->first();
      $tsg0003 = RawMaterial::where('name', 'TSG 0003')->first();
      $tsb0001 = RawMaterial::where('name', 'TSB 0001')->first();
      $ne850125 = RawMaterial::where('name', 'NE 850125')->first();
      $ppa081 = RawMaterial::where('name', 'PPA 08-1')->first();
      $ppa083 = RawMaterial::where('name', 'PPA 08-3')->first();
      $ls0001 = RawMaterial::where('name', 'LS 0001')->first(); // MATERIAL LS 0001
      $df4525 = RawMaterial::where('name', 'DF 4525')->first();
      
      $material_consumption = [
          'VW 110 ' . $this->materialColor => [
            'amount' => $this->getVentJamb() * $qty * 0.083,
            'unit_of_measurement' => $vw110->unit_of_measurement,
            'storage_measure' => $vw110->storage_measure,
            'notes' => $vw110->notes
          ],
          'VW 106 ' . $this->materialColor => [
            'amount' => $this->getVentJamb() * $qty * 0.083,
            'unit_of_measurement' => $vw106->unit_of_measurement,
            'storage_measure' => $vw106->storage_measure,
            'notes' => $vw106->notes
          ],
          'VW 107 ' . $this->materialColor => [
            'amount' => (($this->getVentBottomAndTop() * $qty) + ($qty * $this->getVentBottomAndTop())) * 0.083,
            'unit_of_measurement' => $vw107->unit_of_measurement,
            'storage_measure' => $vw107->storage_measure,
            'notes' => $vw107->notes
          ],
          'VW 101 ' . $this->materialColor => [
            'amount' => $this->height * $qty * 0.083,
            'unit_of_measurement' => $vw101->unit_of_measurement,
            'storage_measure' => $vw101->storage_measure,
            'notes' => $vw101->notes
          ],
          'VW 102 ' . $this->materialColor => [
            'amount' => $this->height * $qty * 0.083,
            'unit_of_measurement' => $vw102->unit_of_measurement,
            'storage_measure' => $vw102->storage_measure,
            'notes' => $vw102->notes
          ],
          'VW 104 ' . $this->materialColor => [
            'amount' => $this->getFixedMeetingRail() * $qty * 0.083,
            'unit_of_measurement' => $vw104->unit_of_measurement,
            'storage_measure' => $vw104->storage_measure,
            'notes' => $vw104->notes
          ],
          'VW 108 ' . $this->materialColor => [
            'amount' => (($this->getGlassHeigth() * 2 * $qty) + ($this->getMoveGlazingBead() * 2 * $qty) + ($this->getGlazingBeadWidth() * 4 * $qty) + ($this->height - 5.31)) * 0.083,
            'unit_of_measurement' => $vw108->unit_of_measurement,
            'storage_measure' => $vw108->storage_measure,
            'notes' => $vw108->notes
          ],
          'VW 111 ' . $this->materialColor => [
            'amount' => ($this->getFrameSill() * 2 * $qty) * 0.083,
            'unit_of_measurement' => $vw111->unit_of_measurement,
            'storage_measure' => $vw111->storage_measure,
            'notes' => $vw111->notes
          ],
          'VW 112 ' . $this->materialColor => [
            'amount' => $this->getSillTrackRail() * $qty * 0.083,
            'unit_of_measurement' => $vw112->unit_of_measurement,
            'storage_measure' => $vw112->storage_measure,
            'notes' => $vw112->notes
          ],
          'WH 0001 ' . $this->materialColor => [
            'amount' => 2 * $qty,
            'unit_of_measurement' => $vh0001->unit_of_measurement,
            'storage_measure' => $vh0001->storage_measure,
            'notes' => $vh0001->notes
          ],
          'RH 0001' => [
            'amount' => 2 * $qty,
            'unit_of_measurement' => $rh0001->unit_of_measurement,
            'storage_measure' => $rh0001->storage_measure,
            'notes' => $rh0001->notes
          ],
          'STS 0001 ' . $this->materialColor => [
            'amount' => 3 * $qty,
            'unit_of_measurement' => $sts0001->unit_of_measurement,
            'storage_measure' => $sts0001->storage_measure,
            'notes' => $sts0001->notes
          ],
          'SC 0001 ' . $this->materialColor => [
            'amount' =>  $this->getScrewCover() * $qty * 0.083,
            'unit_of_measurement' => $sc0001->unit_of_measurement,
            'storage_measure' => $sc0001->storage_measure,
            'notes' => $sc0001->notes
          ],
          'SS 0001 ' . $this->materialColor => [
            'amount' => ($this->getVentBottomAndTop() * 2 * $qty) * 0.083,
            'unit_of_measurement' => $ss0001->unit_of_measurement,
            'storage_measure' => $ss0001->storage_measure,
            'notes' => $ss0001->notes
          ],
          $weatherStripMeetRailSash->name => [
            'amount' => $this->getWeatherStripMeetRailSash() * 0.083 * $qty,
            'unit_of_measurement' => $weatherStripMeetRailSash->unit_of_measurement,
            'storage_measure' => $weatherStripMeetRailSash->storage_measure,
            'notes' => $weatherStripMeetRailSash->notes
          ],
          'W 22254 BL' => [
            'amount' => $this->height * $qty * 0.083,
            'unit_of_measurement' => $w22254->unit_of_measurement,
            'storage_measure' => $w22254->storage_measure,
            'notes' => $w22254->notes
          ],
          'ST 0001' => [
            'amount' => $this->getSteelReiceforment() * $qty * 0.083,
            'unit_of_measurement' => $st0001->unit_of_measurement,
            'storage_measure' => $st0001->storage_measure,
            'notes' => $st0001->notes
          ],
          'TSG 0003' => [
            'amount' => $this->getTSlotSealGlazingBeat() * $qty * 0.083,
            'unit_of_measurement' => $tsg0003->unit_of_measurement,
            'storage_measure' => $tsg0003->storage_measure,
            'notes' => $tsg0003->notes
          ],
          'TSB 0001' => [
            'amount' => $this->height * $qty * 0.083,
            'unit_of_measurement' => $tsb0001->unit_of_measurement,
            'storage_measure' => $tsb0001->storage_measure,
            'notes' => $tsb0001->notes
          ],
          'NE 850125' => [
            'amount' => 16 * $qty,
            'unit_of_measurement' => $ne850125->unit_of_measurement,
            'storage_measure' => $ne850125->storage_measure,
            'notes' => $ne850125->notes
          ],
          'PPA 08-1' => [
            'amount' => 12 * $qty,
            'unit_of_measurement' => $ppa081->unit_of_measurement,
            'storage_measure' => $ppa081->storage_measure,
            'notes' => $ppa081->notes
          ],
          'PPA 08-3' => [
            'amount' => 4 * $qty,
            'unit_of_measurement' => $ppa083->unit_of_measurement,
            'storage_measure' => $ppa083->storage_measure,
            'notes' => $ppa083->notes
          ],
          
          'DF 4525' => [
            'amount' => ($this->height - 5.25) * $qty * 0.083,
            'unit_of_measurement' => $df4525->unit_of_measurement,
            'storage_measure' => $df4525->storage_measure,
            'notes' => $df4525->notes
          ],
      ];

      if ($this->handle == HorizontalRollerHandleEnum::$HANDLE['VENT LATCH'] || $this->handle == HorizontalRollerHandleEnum::$HANDLE['BOTH']) {
          $material_consumption['LS 0001'] = [
              'amount' => 2 * $qty,
              'unit_of_measurement' => $ls0001->unit_of_measurement,
              'storage_measure' => $ls0001->storage_measure,
              'notes' => $ls0001->notes
          ];
          $material_consumption['VW 109 ' . $this->materialColor] = [
              'amount' => 2 * $qty,
              'unit_of_measurement' => $vw109->unit_of_measurement,
              'storage_measure' => $vw109->storage_measure,
              'notes' => $vw109->notes
          ];
      }

      if ($this->handle == HorizontalRollerHandleEnum::$HANDLE['SWIPE LOCK'] || $this->handle == HorizontalRollerHandleEnum::$HANDLE['BOTH']) {
          $material_consumption[$swipeLock->name] = [
              'amount' => 2 * $qty,
              'unit_of_measurement' => $swipeLock->unit_of_measurement,
              'storage_measure' => $swipeLock->storage_measure,
              'notes' => $swipeLock->notes
          ];
      }

      return $material_consumption;
    }

    public function getCuttingList($qty) {
        $cuttingListResult = [];
        $cuttingListResult[] = $this->getCuttingListObject('Frame Head/Sill', 'VW 111 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getFrameSill()), $this->getFrameSill());
        $cuttingListResult[] = $this->getCuttingListObject('Frame Side Cover', 'VW 108 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->height - 5.25), $this->height - 5.25);
        $cuttingListResult[] = $this->getCuttingListObject('Jamb', 'VW 101 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->height), $this->height);
        $cuttingListResult[] = $this->getCuttingListObject('Jamb Operating', 'VW 102 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->height), $this->height);
        $cuttingListResult[] = $this->getCuttingListObject('Meeting Rail', 'VW 104 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getFixedMeetingRail()), $this->getFixedMeetingRail());
        $cuttingListResult[] = $this->getCuttingListObject('Sill Track Rail', 'VW 112 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getSillTrackRail()), $this->getSillTrackRail());
        $cuttingListResult[] = $this->getCuttingListObject('Vent Jamb Meeting', 'VW 110 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getVentJamb()), $this->getVentJamb());
        $cuttingListResult[] = $this->getCuttingListObject('Vent Jamb Lock', 'VW 106 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getVentJamb()), $this->getVentJamb());
        $cuttingListResult[] = $this->getCuttingListObject('Vent Top/Bottom', 'VW 107 ' . $this->materialColor, $qty * 2, $this->getNumberWithFraction($this->getVentBottomAndTop()), $this->getVentBottomAndTop());
        $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Fix Vertical', 'VW 108 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getGlassHeigth()), $this->getGlassHeigth());
        $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Move Vertical', 'VW 108 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getMoveGlazingBead()), $this->getMoveGlazingBead());
        $cuttingListResult[] = $this->getCuttingListObject('Glazing Bead Horizontal', 'VW 108 ' . $this->materialColor, 4 * $qty, $this->getNumberWithFraction($this->getGlazingBeadWidth()), $this->getGlazingBeadWidth());
        $cuttingListResult[] = $this->getCuttingListObject('Punch M.R', ' - ', '-', $this->getNumberWithFraction($this->getGlassWidth() + 0.44 + 0.125), 0);
        $cuttingListResult[] = $this->getCuttingListObject('Steel Reiceforcement square', 'ST 0001', $qty, $this->getNumberWithFraction($this->getSteelReiceforment()), $this->getSteelReiceforment());
        $cuttingListResult[] = $this->getCuttingListObject('Screw Cover', 'SC 0001 ' . $this->materialColor, $qty, $this->getNumberWithFraction($this->getScrewCover()), $this->getScrewCover());
        $cuttingListResult[] = $this->getCuttingListObject('Side Sash PVC', 'SS 0001 ' . $this->materialColor, 2 * $qty, $this->getNumberWithFraction($this->getVentBottomAndTop()), $this->getVentBottomAndTop());
         
        //SILICONE
        if ($this->screenRequired) {
          $cuttingListResult[] = $this->getCuttingListObject('Screen', '', $qty, $this->getNumberWithFraction($this->getScreenWidth()) . ' x ' . $this->getNumberWithFraction($this->getScreenHeigth()), 0);
        }
        $cuttingListResult[] = $this->getCuttingListObject('Glass Fixed', $this->glassType, $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getGlassHeigth()), 0);
        $cuttingListResult[] = $this->getCuttingListObject('Glass Move', $this->glassType, $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getMoveGlassHeight()), 0);

        return $cuttingListResult;
    }

    //TODO: Refactor to use in getCuttingList
    public function getPoScreen($qty) {
        $poScreenResult = [];
        $poScreenResult[] = $this->getCuttingListObject('Screen', '', $qty, $this->getNumberWithFraction($this->getScreenWidth()) . ' x ' . $this->getNumberWithFraction($this->getScreenHeigth()));
        
        return $poScreenResult;
    }

    //TODO: Refactor to use in getCuttingList
    public function getPoGlass($qty) {
        $poScreenResult = [];

        $poScreenResult[] = $this->getCuttingListObject('Glass Fixed', $this->glassType, $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getGlassHeigth()));
        $poScreenResult[] = $this->getCuttingListObject('Glass Move', $this->glassType, $qty, $this->getNumberWithFraction($this->getGlassWidth()) . ' x ' . $this->getNumberWithFraction($this->getMoveGlassHeight()));

        return $poScreenResult;
    }

    public function getHandlePrice() {
        $firstFrameColorLetter = $this->getMaterialColor($this->frameColor);
        $handlePrice = 0;
        if ($this->handle == HorizontalRollerHandleEnum::$HANDLE['VENT LATCH'] || $this->handle == HorizontalRollerHandleEnum::$HANDLE['BOTH']) {
          $ventLatchMaterial = RawMaterial::where('name', 'VW 109 ' . $firstFrameColorLetter)->first(); // MATERIAL 109
          $ventLatchCost = 3 * 0.083 * $ventLatchMaterial->cost_per_unit * 2;
          $lockSprignMaterial = RawMaterial::where('name', 'LS 0001')->first(); // MATERIAL LS 0001
          $lockSprignMaterialCost = $lockSprignMaterial->cost_per_unit * 2;
          $handlePrice += $ventLatchCost + $lockSprignMaterialCost;
        } 
        
        if ($this->handle == HorizontalRollerHandleEnum::$HANDLE['SWIPE LOCK'] || $this->handle == HorizontalRollerHandleEnum::$HANDLE['BOTH']) {
          if ($firstFrameColorLetter == 'BR') {
            $firstFrameColorLetter = 'BL';
          }

          $swipeLock = RawMaterial::where('name', 'SLOCK 0001 ' . $firstFrameColorLetter)->first();
          $handlePrice += $swipeLock->cost_per_unit * 2;
        }

        return $handlePrice;
    }

    public function getUnitPrice() {
        $firstFrameColorLetter = $this->getMaterialColor($this->frameColor);
        $ventJamb106Material = RawMaterial::where('name', 'VW 106 ' . $firstFrameColorLetter)->first(); // MATERIAL 106
        $ventJamb106Cost = $this->getVentJamb() * 0.083 * $ventJamb106Material->cost_per_unit;
        $ventJamb110Material = RawMaterial::where('name', 'VW 110 ' . $firstFrameColorLetter)->first(); // MATERIAL 110
        $ventJamb110Cost = $this->getVentJamb() * 0.083 * $ventJamb110Material->cost_per_unit;
        $ventBottomMaterial = RawMaterial::where('name', 'VW 107 ' . $firstFrameColorLetter)->first(); // MATERIAL 107,
        $ventBottomCost = $this->getVentBottomAndTop() * 0.083 * $ventBottomMaterial->cost_per_unit;
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
        $rolesHousingMaterial = RawMaterial::where('name', 'RH 0001')->first(); // MATERIAL RH 0001
        $rolesHousingCost = 2 * $rolesHousingMaterial->cost_per_unit;
        $steelReinforcementMaterial = RawMaterial::where('name', 'ST 0001')->first(); // MATERIAL ST 0001
        $steelReinforcementCost = $this->getSteelReiceforment() * 0.083 * $steelReinforcementMaterial->cost_per_unit;
        
        $stopSashMaterial = RawMaterial::where('name', 'STS 0001 ' . $firstFrameColorLetter)->first(); // MATERIAL STS 0001 (W or B)
        $stopSashCost = 3 * 0.083 * $stopSashMaterial->cost_per_unit * 2;
        $screwCoverMaterial = RawMaterial::where('name', 'SC 0001 ' . $firstFrameColorLetter)->first(); // SC 0001 (W or B)
        $screwCoverCost =  $this->getScrewCover() * 0.083 * $screwCoverMaterial->cost_per_unit;
        $sideSashClipMaterial = RawMaterial::where('name', 'SS 0001 ' . $firstFrameColorLetter)->first(); // MATERIAL SS 0001 (W or B)
        $sideSashClipCost = ($this->getGlassWidth() + 2.188) * 0.083 * $sideSashClipMaterial->cost_per_unit * 2;
        $settingBlockMaterial = RawMaterial::where('id', 10)->first(); // MATERIAL NE850062
        $settingBlockCost = 16 * $settingBlockMaterial->cost_per_unit;
        if ($this->frameColor == FrameColorEnum::$FRAME_COLOR["WHITE"]) {
          $weatherStripMeetRailSashMaterial = RawMaterial::where('name', 'W 22174 W')->first(); // W 22184 W or B
        } else {
          $weatherStripMeetRailSashMaterial = RawMaterial::where('name', 'W 22174 BL')->first(); // W 22184 W or B
        }
        $weatherStripMeetRailSashCost = (2 * $this->getVentBottomAndTop() + $this->getFixedMeetingRail()) * 0.083 * $weatherStripMeetRailSashMaterial->cost_per_unit;
        $wheatherStripBottomMaterial = RawMaterial::where('name', 'W 22254 BL')->first(); // MATERIAL W 22254 BL
        $wheatherStripBottomCost = $this->height * 0.083 * $wheatherStripBottomMaterial->cost_per_unit;
        $tSlotSealGlazingBeatMaterial = RawMaterial::where('id', 7)->first(); // TSG 0003
        $tSlotSealGlazingBeatCost = ((4 * $this->getGlassHeigth()) + (4 * $this->getGlassWidth())) * 0.083 * $tSlotSealGlazingBeatMaterial->cost_per_unit;
        $tSlotSealFrameBottomMaterial = RawMaterial::where('id', 19)->first(); // TSB 0001
        $tSlotSealFrameBottomCost = $this->height * 0.083 * $tSlotSealFrameBottomMaterial->cost_per_unit;
        $glazingBeatMaterial = RawMaterial::where('name', 'VW 108 ' . $firstFrameColorLetter)->first(); // MATERIAL 108
        $glazingBeatWidthCost = 4 * $this->getGlazingBeadWidth() * 0.083 * $glazingBeatMaterial->cost_per_unit;             
        $glazingBeatHeigthCost = ((2 * $this->getGlassHeigth()) + (2 * $this->getMoveGlazingBead())) * 0.083 * $glazingBeatMaterial->cost_per_unit;
        $frameSideCoverCost = ($this->height - 5.31) * 0.083 * $glazingBeatMaterial->cost_per_unit;// new piece
        $structuralSliconeMaterial = RawMaterial::where('id', 32)->first(); // MATERIAL 105//STRUCTURAL SILICONE
        $structuralSliconeMaterialCost = (($this->getGlassWidth() * 4) + ($this->getGlassHeigth() * 4)) * 0.083 * $structuralSliconeMaterial->cost_per_unit;
        $screwMaterial = RawMaterial::where('name', 'PPA 08-1')->first(); // MATERIAL Screws 8x1
        $screwMaterialCost = $screwMaterial->cost_per_unit * 12;
        $screwMaterial3inch = RawMaterial::where('name', 'PPA 08-3')->first(); // MATERIAL Screws 8x1
        $screwMaterial3Cost = $screwMaterial3inch->cost_per_unit * 4;
        $dobleFaceMaterial = RawMaterial::where('name', 'DF 4525')->first(); // MATERIAL LS 0001
        $dobleFacenMaterialCost = ($this->height - 5.25) * 0.083 * $dobleFaceMaterial->cost_per_unit;
        $handlePrice = $this->getHandlePrice();
        // GET OTHER BILLS
        $workBill = config('custom.work_bill');
        $rentBill = config('custom.rent_bill');
        $electricityBill = config('custom.electricity_bill');
        $internetBill = config('custom.internet_bill');
        $otherBill = config('custom.other_bill');
        $screen_price_by_sqft = config('custom.screen_price_by_sqft');
        $packing = config('custom.packing');
        $cornerSilicone = config('custom.corner_silicone');

        //GET MUNTIN COST
        $muntinPriceBySqft = config('custom.muntin_price_by_sqft');
        $muntinCost = 0;
        if ($this->muntinPanels) {
          $horizontalMuntinsCost = ($this->horizontalLines - 1) * $this->getGlassWidth() * $muntinPriceBySqft * 0.083;
          $verticalMuntingCost = ($this->verticalLines - 1) * $this->getMoveGlassHeight() * $muntinPriceBySqft * 0.083;

          if (!empty($this->muntinInteriorStyle)) {
            $muntinCost = $horizontalMuntinsCost + $verticalMuntingCost;
          }
          if(!empty($this->muntinExteriorStyle)) {
            $muntinCost += $horizontalMuntinsCost + $verticalMuntingCost;
          }

          if ($this->panelA == true && $this->panelB == true) {
            $muntinCost *= 2;
          }
        }

        $glassMaterial = RawMaterial::where('name', $this->glassType)->first(); // MATERIAL Glass
        $glassCost = $this->getGlassSize($this->getGlassHeigth()) * $this->getGlassSize($this->getGlassWidth()) / 144 * $glassMaterial->cost_per_unit;
        $glassCostMove = $this->getGlassSize($this->getMoveGlassHeight()) * $this->getGlassSize($this->getGlassWidth()) / 144 * $glassMaterial->cost_per_unit;
        $screenCost = 0;
        if ($this->screenRequired) {
          $screenCost = $this->getScreenWidth() * $this->getScreenHeigth() / 144 * $screen_price_by_sqft;
        }

        /*  echo "ventJamb110Cost : " . $ventJamb110Cost . "<br/>";
          echo "ventJamb106Cost : " . $ventJamb106Cost . "<br/>";
          echo "ventBottomCost : " . $ventBottomCost . "<br/>";
          echo "ventTopCost : " . $ventTopCost . "<br/>";
          echo "jamb101Cost : " . $jamb101Cost . "<br/>";
          echo "jamb102Cost : " . $jamb102Cost . "<br/>";
          echo "fixedMeetingRailMaterialCost : " . $fixedMeetingRailMaterialCost . "<br/>";
          echo "frameHeadSillCost : " . $frameHeadSillCost . "<br/>";
          echo "stilTrackRailCost : " . $stilTrackRailCost . "<br/>";
          echo "weepHoleCost : " . $weepHoleCost . "<br/>";
          echo "rolesHousingCost : " . $rolesHousingCost . "<br/>";
          echo "steelReinforcementCost : " . $steelReinforcementCost . "<br/>";
          echo "handle : " . $handlePrice . "<br/>";
          echo "stopSashCost : " . $stopSashCost . "<br/>";
          echo "screwCoverCost : " . $screwCoverCost . "<br/>";
          echo "sideSashClipCost : " . $sideSashClipCost . "<br/>";
          echo "settingBlockCost : " . $settingBlockCost . "<br/>";
          echo "weatherStripMeetRailSashCost : " . $weatherStripMeetRailSashCost . "<br/>";
          echo "wheatherStripBottomCost : " . $wheatherStripBottomCost . "<br/>";
          echo "tSlotSealGlazingBeatCost : " . $tSlotSealGlazingBeatCost . "<br/>";
          echo "tSlotSealFrameBottomCost : " . $tSlotSealFrameBottomCost . "<br/>";
          echo "glazingBeatWidthCost : " . $glazingBeatWidthCost + $glazingBeatHeigthCost + $frameSideCoverCost . "<br/>";
          echo "structuralSliconeMaterialCost : " . $structuralSliconeMaterialCost . "<br/>";
          echo "glassCost : " . $glassCost . "<br/>";
          echo "glassCostMove : " . $glassCostMove . "<br/>";
          echo "screenCost : " . $screenCost . "<br/>";
          echo "Screws 8-1: " . $screwMaterialCost . "<br/>";
          echo "Screws 8-3: " . $screwMaterial3Cost . "<br/>";
          echo "Doble Face: " . $dobleFacenMaterialCost . "<br/>";
          echo "packing: " . $packing . "<br/>";
          echo "Mounting Cost: " . $muntinCost . "<br/>";
          echo "Total: " . round($ventJamb106Cost +
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
          $handlePrice +
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
          $frameSideCoverCost +
          $structuralSliconeMaterialCost +
          $screwMaterialCost +
          $screwMaterial3Cost +
          $dobleFacenMaterialCost +
          $glassCost +
          $glassCostMove +
          $packing +
          $muntinCost +
          $screenCost, 2);
          die; */

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
          $handlePrice +
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
          $screwMaterialCost +
          $screwMaterial3Cost +
          $dobleFacenMaterialCost +
          $screenCost +
          $frameSideCoverCost +
          $packing +
          $workBill +
          $rentBill +
          $electricityBill +
          $internetBill +
          $cornerSilicone +
          $muntinCost +
          $otherBill;

        //GET COMPANY MOCKUP
        /*$companyMockup = $this->getCompanyMockup();
        $companyMockupCost = $unitPriceCost * $companyMockup / 100;

        $promotion = $this->getCompanyPromotion();
        $promotionCost = ($unitPriceCost + $companyMockupCost) * $promotion / 100;*/

        return $unitPriceCost;
    }
}