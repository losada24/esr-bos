<?php

namespace App\Rules;

use App\Enum\ProductSystemEnum;
use App\Models\Order;
use App\Products\FixedWindowsProduct;
use App\Products\HorizontalRollerProduct;
use App\Products\SingleHuntProduct;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateOrderAttachments implements DataAwareRule, ValidationRule
{
    protected $data = [];
    // public $fileType;

    public function __construct()
    {
        // $this->fileType = $fileType;
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
      // dd($this->data, $value, $attribute);
      if ($value == 1 && $attribute == 'walk_trough') {
       
        if (!isset($this->data['walk_trough_attach'])) {
            $hasAttachments = Order::whereHas('attachments', function ($query) {
              $query->where('file_type', 'walk_trough_attach');
            })->where('id', $this->data['order_id'])->exists();

            if (!$hasAttachments) {
              $fail('The Walk through attachment is required');
            }
        }
          
      }
      if ($value == 1 && $attribute == 'inspection') {
       
        if (!isset($this->data['inspection_attach'])) {
            $hasAttachments = Order::whereHas('attachments', function ($query) {
              $query->where('file_type', 'inspection_attach');
            })->where('id', $this->data['order_id'])->exists();

            if (!$hasAttachments) {
              $fail('The Inspection attachment is required');
            }
        }
          
      }
      if ($value == 1 && $attribute == 'pre_inspection') {
       
        if (!isset($this->data['pre_inspection_attach'])) {
            $hasAttachments = Order::whereHas('attachments', function ($query) {
              $query->where('file_type', 'pre_inspection_attach');
            })->where('id', $this->data['order_id'])->exists();

            if (!$hasAttachments) {
              $fail('The  Pre inspection attachment is required');
            }
        }
          
      }

    }
    
}
