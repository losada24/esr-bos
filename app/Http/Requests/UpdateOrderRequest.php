<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enum\FrameColorEnum;
use App\Enum\ServiceEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\TypeOfFinancing;
use App\Rules\ValidateOrderStatus;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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
          'client_name' => 'required|string|max:255',
          // 'last_name' => 'required|string|max:255',
          'phone' => 'required|string|max:255',
          'email' => 'nullable|email|max:255',
          'name' => 'required|string|max:255',
           //'order_number' => 'required|integer',
           'order_number' => 'required|string|max:255',
          'job_address' => 'required|string|max:255',
          'owners' => 'required|array',
          'owners.*' => 'required|integer|exists:users,id',
          'type_of_work_id' =>  [
            'nullable',
             Rule::when(
               fn($input) => $input->service == ServiceEnum::INSTALLATION->value
               , ['required','integer','exists:type_of_works,id',]
             ),
         ],
          'type_of_housing_id' => [
            'nullable',
            Rule::when(
              fn($input) => $input->service == ServiceEnum::INSTALLATION->value
              , ['required','integer','exists:types_of_housing,id',]
            ),
          ],
          'installation_teams' => 'nullable|array',
          'installation_teams.*' => 'required|integer|exists:installation_teams,id',
          'supervisor_id' => 'nullable|integer|exists:users,id',
          'travel_cost_id' =>[
            'nullable',
            Rule::when(
              fn($input) => $input->service == ServiceEnum::INSTALLATION->value
              , ['required','integer','exists:travel_costs,id',]
            ),
          ],
          'duration_of_work_id' => [
            'nullable',
            Rule::when(
              fn($input) => $input->service == ServiceEnum::INSTALLATION->value
              , ['required', 'integer', 'exists:duration_of_works,id',]
            ),
          ],
          'additional_travel_costs' => 'nullable|numeric',
          'cost_delivery' => 'nullable|numeric',
          'cost_city_fee' => 'nullable|numeric',
          'project_amount' => 'nullable|numeric',
          'city' => 'nullable|string|max:100',
          'initial_payment_percentage' => 'nullable|numeric',
          'payment_definition' => 'boolean',
          'method_of_payment' =>  [
            'required',
            'string',
            Rule::in(
              MethodOfPayment::CASH->value,
              MethodOfPayment::FINANCED->value,
              MethodOfPayment::FINANCEDCASH->value,
              MethodOfPayment::AIA->value
            )
          ],
          'type_of_financing' => [
            'nullable',
            'string',
            Rule::in(
              TypeOfFinancing::WELLS_FARGO->value,
              TypeOfFinancing::HOME_RUN->value,
              TypeOfFinancing::SUN_LIGHT->value,
              TypeOfFinancing::SLIN->value,
              TypeOfFinancing::YGREEN->value,
            )
          ],
          'status' =>  [
            'required',
            'string',
            Rule::in(
              OrderStatusEnum::PLANNED->value,
              OrderStatusEnum::CONFIRMED->value,
              OrderStatusEnum::EXECUTION->value,
              OrderStatusEnum::SUPERVISION->value,
              OrderStatusEnum::INSPECTION->value,
              OrderStatusEnum::FINISH->value,
              OrderStatusEnum::FINAL_INSPECTION->value,
              OrderStatusEnum::FINAL_COLLECT->value,
              OrderStatusEnum::ON_HOLD->value,
              OrderStatusEnum::DELIVERY_CONFIRMED->value
            ),
            new ValidateOrderStatus
          ],
          'frame_color' => [
            'nullable',
            Rule::when(
              fn($input) => $input->service == ServiceEnum::INSTALLATION->value
              , ['required', 'string', Rule::in([
                FrameColorEnum::WHITE->value,
                FrameColorEnum::BLACK->value,
                FrameColorEnum::BRONZE->value,
                FrameColorEnum::CLEAR_ANODIZED->value
              ])]
            ),
          ],
          'service' => [
              'required',
              'string',
              Rule::in([
                ServiceEnum::DELIVERY->value, 
                ServiceEnum::INSTALLATION->value,
                ServiceEnum::PICKUP->value
              ]),
            ],
          'contract_signing_date' => 'required|date_format:Y-m-d',
          'payment_factory_date' => 'required|date_format:Y-m-d',
          'eta_date' => 'required|date_format:Y-m-d',
          'installation_end_date' => 'nullable|date_format:Y-m-d',
          'delivery_date' => 'nullable|date_format:Y-m-d',
          'entry_date' => 'nullable|date_format:Y-m-d',
          'installation_date' => 'nullable|date_format:Y-m-d',
          'city_permits' => 'boolean',
          'association_permits' => 'boolean',
          'equipment_rental' => 'boolean',
          'notes' => 'nullable|string|max:1000',
          'attachments' => 'nullable|array',
          'attachments.*' => 'file|mimes:jpeg,png,jpg,pdf,docx,doc,xlsx|max:10240',
          'orderProducts' => 'required|array',
          'orderProducts.*.type_of_product_id' => 'required|integer|exists:type_of_products,id',
          'orderProducts.*.product_category_id' => 'required|integer|exists:product_categories,id',
          'orderProducts.*.product_config_id' => 'required|integer|exists:product_configs,id',
          'orderProducts.*.width' => 'nullable|numeric',
          'orderProducts.*.height' => 'nullable|numeric',
          'orderProducts.*.qty' => 'required|numeric',
          'orderProducts.*.unit_price' => 'required|numeric',
          'orderProducts.*.total_price' => 'required|numeric',
          'orderProducts.*.unit_price_with_extraworks' => 'required|numeric',
          'orderProducts.*.total_price_with_extraworks' => 'required|numeric',
          'orderProducts.*.extra_work_price' => 'required|numeric',
          'orderProducts.*.extra_works' => 'nullable|array',
        ];
    }
}
