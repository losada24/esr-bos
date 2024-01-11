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

    public static function getSystemPressure($systemName) {
      return match($systemName) {
        self::$FIXED_WINDOWS => '+75/-75 psf',
        self::$SINGLE_HUNG => '+70/-70 psf',
        self::$HORIZONTAL_ROLLER => '+70/-70 psf',
        default => '+75/-75 psf'
      };
    }

    public static function getSystemNoa($systemName) {
      return match($systemName) {
        self::$FIXED_WINDOWS => 'F.B.C FL 41809',
        self::$SINGLE_HUNG => 'NOA #23-0921.02',
        self::$HORIZONTAL_ROLLER => 'F.B.C FL 41810',
        default => ''
      };
    }
}
