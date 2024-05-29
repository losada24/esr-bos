<?php

namespace App\Enum;

class GlassColorEnum {

  public static $BRONZE_GLASS_COLOR = 'BRONZE';
  public static $OBSCURE_PRIVACY = 'OBSCURE/PRIVACY';

  public static $GLASS_COLOR = [
    'BRONZE' => 'BRONZE',
    'CLEAR' => 'CLEAR',
    'GRAY' => 'GRAY',
    'GREEN' => 'GREEN',
    'OBSCURE/PRIVACY' => 'OBSCURE/PRIVACY',
  ];

  public static function getRegularGlassColor() {
    return array_filter(self::$GLASS_COLOR, function($key) {
      return $key != self::$BRONZE_GLASS_COLOR;
    }, ARRAY_FILTER_USE_KEY);
  }

  public static function getExternalGlassColor() {
    return array_filter(self::$GLASS_COLOR, function($key) {
      return $key != self::$OBSCURE_PRIVACY;
    }, ARRAY_FILTER_USE_KEY);
  }
}
