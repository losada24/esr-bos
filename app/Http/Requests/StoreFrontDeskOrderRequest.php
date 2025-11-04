<?php

namespace App\Http\Requests;

use App\Enum\ContactSourceEnum;
use App\Enum\FrameColorEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\PlaningDateSupervisorEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Enum\TypeOfFinancing;
use App\Rules\ValidateOrderStatus;
use Illuminate\Validation\Rule;

class StoreFrontDeskOrderRequest extends FormRequest
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
           // 'client_id' => 'nullable|integer|exists:clients,id',
            'client_name' => 'required|string|max:255',
            // 'last_name' => 'required|string|max:255',
            //'phone' => 'required|string|max:255',
            'phone' => [
              'required',
              'regex:/^\d{10}$/'
            ],
            'status' =>  [
            'required',
            'string',
              Rule::in(
                OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
                OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
                OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
                OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
                OrderStatusEnum::QUALIFIED->value,
              )
            ],
            'source' =>  [
            'required',
            'string',
              Rule::in(
                ContactSourceEnum::TIK_TOK->value,
                ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
                ContactSourceEnum::META->value,
                ContactSourceEnum::DESTINO_TOLK->value,
                ContactSourceEnum::RESOURCE_MAGAZINE->value,
                ContactSourceEnum::BANNER_PUBLICITARIO->value,
                ContactSourceEnum::PICHY_BOYS->value,
                ContactSourceEnum::GOOGLE_MY_BUSINESS->value,
              )
            ],
            'notes' => 'nullable|string|max:1000',
           
        ];
    }
}
