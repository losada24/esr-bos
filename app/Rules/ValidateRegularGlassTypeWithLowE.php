<?php

namespace App\Rules;

use App\Enum\ProductSystemEnum;
use App\Products\FixedWindowsProduct;
use App\Products\HorizontalRollerProduct;
use App\Products\SingleHuntProduct;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateRegularGlassTypeWithLowE implements DataAwareRule, ValidationRule
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
      $glassWidth = 0;
      $glassHeight = 0;
      if ($this->system == ProductSystemEnum::$FIXED_WINDOWS) {
        $fixedWindows = new FixedWindowsProduct(
          $this->data['width'],
          $this->data['height'],
          $this->data['frame_color'],
          $this->data['glass_color'],
        );
        $glassWidth = $fixedWindows->getGlassWidth();
        $glassHeight = $fixedWindows->getGlassHeigth();
      } else if ($this->system == ProductSystemEnum::$SINGLE_HUNG) {
        $singleHung = new SingleHuntProduct(
          $this->data['width'],
          $this->data['height'],
          $this->data['frame_color'],
          $this->data['glass_color'],
          $this->data['screen']
        );
        $glassHeight = $singleHung->getGlassHeigth();
        $glassWidth = $singleHung->getGlassWidth();
      } else if ($this->system == ProductSystemEnum::$HORIZONTAL_ROLLER) {
        $horizontalRoller = new HorizontalRollerProduct(
          $this->data['width'],
          $this->data['height'],
          $this->data['frame_color'],
          $this->data['glass_color'],
          $this->data['screen']
        );
        $glassHeight = $horizontalRoller->getMoveGlassHeight();
        $glassWidth = $horizontalRoller->getGlassWidth();
      }
      if (($glassWidth < 13.75 || $glassHeight < 13.75 ) && $this->data['low_e'] != 'NONE') {
        $fail('Glass size is too small for Low-E. Please choose other Low-E option');
      }
    }
}
