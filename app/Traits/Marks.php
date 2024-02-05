<?php

namespace App\Traits;


trait Marks {

  public function createMarkWithLeadingZero($mark, $length) {
      return sprintf("%0{$length}d", $mark);
  }
}
