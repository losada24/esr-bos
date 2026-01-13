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
            ContactSourceEnum::EXTERNAL_REFERAL->value,
            ContactSourceEnum::INTERNAL_REFERAL->value,
            ContactSourceEnum::SIGNS->value,
            ContactSourceEnum::WALK_IN->value,
            ContactSourceEnum::ESW_REFER->value,
            ContactSourceEnum::ESR_REFER->value,
            ContactSourceEnum::YOUTUBE->value,
            ContactSourceEnum::NEW_ORDER ->value,
            ContactSourceEnum::GOOGLE_ADS->value,
              )
            ],
            'notes' => 'nullable|string|max:1000',
            'name_check' => ['boolean'],
            'address_check' => ['boolean'],
            'amount_check' => ['boolean'],
            'email_check' => ['boolean'],
           
        ];
    }
}
