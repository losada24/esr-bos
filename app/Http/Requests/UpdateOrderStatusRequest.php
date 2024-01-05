<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enum\OrderStatusEnum;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'id' => 'required|exists:orders,id',
            'notes' => 'nullable|string|max:500',
            'status' => [
              'required',
              'string',
              'max:50',
              Rule::in([
                OrderStatusEnum::$ESTIMATE,
                OrderStatusEnum::$PRODUCTION,
                OrderStatusEnum::$ORDER_COMPLETED,
                OrderStatusEnum::$READY_FOR_DELIVERY,
                OrderStatusEnum::$PRODUCTION_COMPLETED
              ])
            ]
        ];
    }
}
