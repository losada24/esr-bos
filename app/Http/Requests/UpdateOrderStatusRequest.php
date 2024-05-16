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
            'notes' => 'required|string|max:500',
            'status' => [
              'required',
              'string',
              'max:50',
              Rule::in([
                OrderStatusEnum::$ESTIMATE,
                OrderStatusEnum::$PRODUCTION,
                OrderStatusEnum::$MATERIAL_REVIEWED,
                OrderStatusEnum::$ORDER_COMPLETED,
                OrderStatusEnum::$READY_FOR_DELIVERY,
                OrderStatusEnum::$PRODUCTION_COMPLETED,
                OrderStatusEnum::$DELIVERED,
                OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED,
                OrderStatusEnum::$PARTIAL_DELIVERED,
                OrderStatusEnum::$PRODUCTION_IN_PROGRESS,
                OrderStatusEnum::$SCHEDULED_PRODUCTION,
                OrderStatusEnum::$SUB_DEALER_ESTIMATE,
                OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY,
                OrderStatusEnum::$READY_FOR_PICKUP,
                OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP,
                OrderStatusEnum::$PICKED_UP,
                OrderStatusEnum::$PARTIAL_PICKED_UP
              ])
            ]
        ];
    }
}
