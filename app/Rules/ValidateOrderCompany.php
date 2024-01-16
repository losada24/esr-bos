<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Order;

class ValidateOrderCompany implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
      $order = Order::find($value);
      if ($order->company_id != auth()->user()->company_id) {
        $fail('The :attribute field is invalid.');
      }
    }
}
