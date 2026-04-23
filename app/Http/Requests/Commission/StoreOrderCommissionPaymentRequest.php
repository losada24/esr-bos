<?php

namespace App\Http\Requests\Commission;

use App\Enum\CommissionPaymentKindEnum;
use App\Enum\CommissionPaymentStatusEnum;
use App\Enum\CommissionSplitTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderCommissionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_kind' => ['nullable', 'string', Rule::in(array_column(CommissionPaymentKindEnum::cases(), 'value'))],
            'split_type' => ['required', 'string', Rule::in(array_column(CommissionSplitTypeEnum::cases(), 'value'))],
            'split_value' => ['required', 'numeric'],
            'status' => ['required', 'string', Rule::in(array_column(CommissionPaymentStatusEnum::cases(), 'value'))],
            'other_cost_amount' => ['nullable', 'numeric'],
            'other_cost_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
