<?php

namespace App\Http\Requests\Commission;

use App\Enum\CommissionBeneficiaryRelationEnum;
use App\Enum\CommissionBeneficiarySourceEnum;
use App\Enum\CommissionCalculationTypeEnum;
use App\Enum\CommissionPaymentStatusEnum;
use App\Enum\CommissionSplitTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sourceType = $this->input('beneficiary_source_type');
        $calculationType = $this->input('calculation_type');

        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'beneficiary_source_type' => ['required', 'string', Rule::in(array_column(CommissionBeneficiarySourceEnum::cases(), 'value'))],
            'beneficiary_source_id' => [
                Rule::requiredIf($sourceType !== CommissionBeneficiarySourceEnum::EXTERNAL->value),
                'nullable',
                'integer',
            ],
            'beneficiary_relation' => ['required', 'string', Rule::in(array_column(CommissionBeneficiaryRelationEnum::cases(), 'value'))],
            'calculation_type' => ['required', 'string', Rule::in(array_column(CommissionCalculationTypeEnum::cases(), 'value'))],
            'fee_amount_snapshot' => ['nullable', 'numeric', 'min:0'],
            'percentage_value' => [
                Rule::requiredIf($calculationType === CommissionCalculationTypeEnum::PERCENTAGE->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'fixed_amount' => [
                Rule::requiredIf($calculationType === CommissionCalculationTypeEnum::FIXED->value),
                'nullable',
                'numeric',
            ],
            'other_cost_amount' => ['nullable', 'numeric'],
            'other_cost_notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'external_beneficiary_id' => ['nullable', 'integer', 'exists:external_commission_beneficiaries,id'],
            'external_name' => [
                Rule::requiredIf(
                    $sourceType === CommissionBeneficiarySourceEnum::EXTERNAL->value
                    && ! $this->filled('external_beneficiary_id')
                ),
                'nullable',
                'string',
                'max:255',
            ],
            'external_email' => ['nullable', 'email'],
            'external_phone' => ['nullable', 'string', 'max:100'],
            'external_company_name' => ['nullable', 'string', 'max:255'],
            'payments' => ['nullable', 'array', 'min:1'],
            'payments.*.split_type' => ['required_with:payments', 'string', Rule::in(array_column(CommissionSplitTypeEnum::cases(), 'value'))],
            'payments.*.split_value' => ['required_with:payments', 'numeric'],
            'payments.*.status' => ['required_with:payments', 'string', Rule::in(array_column(CommissionPaymentStatusEnum::cases(), 'value'))],
            'payments.*.other_cost_amount' => ['nullable', 'numeric'],
            'payments.*.other_cost_notes' => ['nullable', 'string'],
            'payments.*.notes' => ['nullable', 'string'],
            'payments.*.paid_at' => ['nullable', 'date'],
        ];
    }
}
