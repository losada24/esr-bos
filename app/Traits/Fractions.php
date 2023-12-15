<?php

namespace App\Traits;


trait Fractions {

    public function getFractionObject($min, $max, $label) {
      $fractionObject = new \stdClass();
      $fractionObject->min = $min;
      $fractionObject->max = $max;
      $fractionObject->label = $label;
      return $fractionObject;
    }

    public function availableFractions() {
      $fractions = [
        $this->getFractionObject(0.001, 0.093, '1/16'),
        $this->getFractionObject(0.094, 0.156, '1/8'),
        $this->getFractionObject(0.157, 0.218, '3/16'),
        $this->getFractionObject(0.219, 0.281, '1/4'),
        $this->getFractionObject(0.282, 0.343, '5/16'),
        $this->getFractionObject(0.344, 0.406, '3/8'),
        $this->getFractionObject(0.407, 0.468, '7/16'),
        $this->getFractionObject(0.469, 0.531, '1/2'),
        $this->getFractionObject(0.532, 0.593, '9/16'),
        $this->getFractionObject(0.594, 0.656, '5/8'),
        $this->getFractionObject(0.657, 0.718, '11/16'),
        $this->getFractionObject(0.719, 0.781, '3/4'),
        $this->getFractionObject(0.782, 0.843, '13/16'),
        $this->getFractionObject(0.844, 0.906, '7/8'),
        $this->getFractionObject(0.907, 0.968, '15/16')
      ];

      return $fractions;
    }

    public function getDecimalPart($number) {
      $whole = floor($number);
      return $number - $whole;
    }

    public function getNumberWithFraction($number) {
      $fractions = $this->availableFractions();
      $result = "";
      $decimal = $this->getDecimalPart($number);
      foreach ($fractions as $fraction) {
        if ($decimal >= $fraction->min && $decimal <= $fraction->max) {
          $result = $fraction->label;
          break;
        }
      }

      return floor($number) . " " . $result;
    }

}