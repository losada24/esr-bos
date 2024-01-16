<?php

namespace App\Products;

class Glass {
  public function __construct(public $glass_type, public $glass_color, public $low_e, public $privacy)
  {}

  // HR, FW
  public function getGlass316() {
      $lowEStart = $this->glass_color == 'CLEAR' && $this->low_e != 'NONE' ? $this->low_e : '';
      $lowEEnd = $this->glass_color != 'CLEAR' && $this->low_e != 'NONE' ? $this->low_e : '';

      $strParams = [
        ':firstGlass' => "3/16 HS {$this->glass_color} $lowEStart",
        ':interlayer' => "+0.09PVB t {$this->privacy}",
        ':lastGlass' => "3/16 HS CLEAR $lowEEnd",
        ':glassType' => $this->glass_type
      ];

      return strtr(":firstGlass :interlayer :lastGlass(:glassType)", $strParams);
  }

  // SH
  public function getGlass18() {
      $lowEStart = $this->glass_color == 'CLEAR' && $this->low_e != 'NONE' ? $this->low_e : '';
      $lowEEnd = $this->glass_color != 'CLEAR' && $this->low_e != 'NONE' ? $this->low_e : '';

      $strParams = [
        ':firstGlass' => "1/8 HS {$this->glass_color} $lowEStart",
        ':interlayer' => "+0.09PVB s {$this->privacy}",
        ':lastGlass' => "1/8 HS CLEAR $lowEEnd",
        ':glassType' => $this->glass_type
      ];

      return strtr(":firstGlass :interlayer :lastGlass(:glassType)", $strParams);
  }
}