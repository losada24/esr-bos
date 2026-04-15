<?php

namespace App\Http\Requests;

use App\Enum\ServiceControlClosureResultEnum;
use App\Enum\ServiceControlPriorityEnum;
use App\Enum\ServiceControlStatusEnum;
use App\Enum\ServiceControlTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'service_name' => ['required', 'string', 'max:255'],
            'service_id' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'string', Rule::in(array_column(ServiceControlTypeEnum::cases(), 'value'))],
            'description' => ['nullable', 'string'],
            'requires_part' => ['boolean'],
            'requested_parts' => ['boolean'],
            'parts_available' => ['boolean'],
            'service_status' => ['required', 'string', Rule::in(array_column(ServiceControlStatusEnum::cases(), 'value'))],
            'priority' => ['required', 'string', Rule::in(array_column(ServiceControlPriorityEnum::cases(), 'value'))],
            'target_date' => ['nullable', 'date_format:Y-m-d'],
            'scheduled_date' => ['nullable', 'date_format:Y-m-d'],
            'executed_date' => ['nullable', 'date_format:Y-m-d'],
            'closure_result' => [
                'nullable',
                'string',
                Rule::requiredIf(fn () => $this->input('service_status') === ServiceControlStatusEnum::CLOSED->value),
                Rule::in(array_column(ServiceControlClosureResultEnum::cases(), 'value')),
            ],
            'observations' => ['nullable', 'string'],
        ];
    }
}
