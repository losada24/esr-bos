<?php

namespace App\Enum;

class ProductSystemEnum
{
    public static $FIXED_WINDOWS = 'FIXED WINDOWS';
    public static $SINGLE_HUNG = 'SINGLE HUNG';
    public static $HORIZONTAL_ROLLER = 'HORIZONTAL ROLLER';

    public static function getSystemNameAbbr($systemName) {
      return match($systemName) {
        self::$FIXED_WINDOWS => 'FW',
        self::$SINGLE_HUNG => 'SH',
        self::$HORIZONTAL_ROLLER => 'HR',
        default => 'FW'
      };
    }
}
