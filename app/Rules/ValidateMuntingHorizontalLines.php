<?php

namespace App\Rules;

use App\Enum\ProductSystemEnum;
use App\Products\FixedWindowsProduct;
use App\Products\HorizontalRollerProduct;
use App\Products\SingleHuntProduct;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateMuntingHorizontalLines implements DataAwareRule, ValidationRule
{
    protected $data = [];
    public $system;

    public function __construct($system)
    {
        $this->system = $system;
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
      if ($value != 0 && $value < 2) {
        $fail('Minimun amount of lines is 2. Please adjust the number of lines.');
      }
      
      $glassHeight = 0;
      if ($this->system == ProductSystemEnum::$FIXED_WINDOWS) {
        $fixedWindows = new FixedWindowsProduct(
          $this->data['width'],
          $this->data['height'],
          $this->data['frame_color'],
          $this->data['glass_color'],
        );
        $glassHeight = $fixedWindows->getGlassHeigth();
      }
      else if ($this->system == ProductSystemEnum::$SINGLE_HUNG) {
        $singleHung = new SingleHuntProduct(
          $this->data['width'],
          $this->data['height'],
          $this->data['frame_color'],
          $this->data['glass_color'],
          $this->data['screen']
        );
        $glassHeight = $singleHung->getGlassHeigth();
      }
      else if ($this->system == ProductSystemEnum::$HORIZONTAL_ROLLER) {
        $singleHung = new HorizontalRollerProduct(
          $this->data['width'],
          $this->data['height'],
          $this->data['frame_color'],
          $this->data['glass_color'],
          $this->data['screen']
        );
        $glassHeight = $singleHung->getMoveGlassHeight();
      }

      $whiteSpaceAndMuntinSize = ($value - 1) + ($value * 2);

      if ($glassHeight < $whiteSpaceAndMuntinSize) {
        $fail('Horizontal lines exceed glass width. Please adjust the number of lines.');
      }
    }
}
