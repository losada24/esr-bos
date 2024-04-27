<?php

namespace App\Rules;

use App\Enum\RoleEnum;
use App\Models\OrderStatus;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateStatusNoteOwner implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
      $orderStatus = OrderStatus::find($value);
      if ($orderStatus->user_id != auth()->user()->id && !auth()->user()->hasRole(RoleEnum::$ADMIN) && !auth()->user()->hasRole(RoleEnum::$ACCOUNT_MANAGER)) {
        $fail('You are not authorized to access this page. This note is not created by you.');
      }
    }
}
