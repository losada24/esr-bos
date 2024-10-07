<?php

namespace App\Rules;

use App\Enum\OrderStatusEnum;
use App\Enum\ServiceEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;

class ValidateOrderStatus implements DataAwareRule, ValidationRule
{
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
      if (
        $this->data['service'] == ServiceEnum::INSTALLATION->value 
        && $this->data['status'] == OrderStatusEnum::CONFIRMED->value && (
          $this->data['supervisor_id'] == '' || (
            !isset($this->data['installation_teams']) ||
            count($this->data['installation_teams']) == 0
          ))) {
        $fail('To confirm the order, you must assign an installation team and Supervisor.');
      }
    }
}
