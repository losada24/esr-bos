<?php

namespace App\Rules;

use App\Enum\ExternalProductEnum;
use App\Models\ExternalProductConfiguration;
use App\Traits\ExternalProductTrait;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateMullionHeight implements DataAwareRule, ValidationRule
{

  use ExternalProductTrait;

  protected $data = [];

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
      $extra = $this->getExtraMullionFields(
        ExternalProductConfiguration::where('external_product', ExternalProductEnum::$MULLION)->get()
      );
      $max_height_index = array_search($this->data['config'], array_column($extra, 'configuration'));
      $max_height = $extra[$max_height_index]['height'];
      if ($value > $max_height) {
        $fail('Maximun height is ' . $max_height . ' inches. Please adjust the height.');
      }
    }
}
