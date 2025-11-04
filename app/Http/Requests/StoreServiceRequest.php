<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\ServiceEnum;
use App\Enum\TypeOfFinancing;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_name' => 'required|string|max:255',
            //'phone' => 'required|string|max:255',
             'phone' => [
              'required',
              'regex:/^\d{10}$/'
            ],
            'email' => 'nullable|email|max:255',
            'name' => 'required|string|max:255',
            'order_number' => 'nullable|string|max:255',
            'job_address' => 'required|string|max:255',
            'city' => 'nullable|string|max:100',
            'job_state' => 'nullable|string|max:100',
            'job_zip' => 'nullable|string|max:100',
            'owners' => 'nullable|array',
            'owners.*' => 'integer|exists:users,id',
            'service' => [
                'required',
                'string',
                Rule::in([ServiceEnum::SERVICE->value]),
            ],
            'vip_clients' => 'boolean',
            'vip_notes' => 'nullable|string|max:1000',
            'do_not_send_email' => 'boolean',
            'additional_travel_costs' => 'nullable|numeric',
            'travel_cost_id' => 'nullable|integer|exists:travel_costs,id',
            'duration_of_work_id' => 'nullable|integer|exists:duration_of_works,id',
            'is_new_travel_cost' => 'boolean',
            'new_travel_cost' => 'nullable|numeric',
            'installation_teams' => 'nullable|array',
            'installation_teams.*' => 'integer|exists:installation_teams,id',
            'method_of_payment' => [
                'required',
                'string',
                Rule::in(
                    MethodOfPayment::CASH->value,
                    MethodOfPayment::FINANCED->value,
                    MethodOfPayment::FINANCEDCASH->value,
                    MethodOfPayment::AIA->value,
                ),
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
                    TypeOfFinancing::GOOD_LEAP->value,
                ),
            ],
            'status' => [
                'required',
                'string',
                Rule::in(
                    OrderStatusEnum::PLANNED->value,
                    OrderStatusEnum::CONFIRMED->value,
                    OrderStatusEnum::REVIEW->value,
                ),
            ],
            'supervisor_id' => 'nullable|integer|exists:users,id',
            'project_amount' => 'nullable|numeric',
            'entry_date' => 'nullable|date_format:Y-m-d',
            'delivery_date' => 'nullable|date_format:Y-m-d',
            'installation_date' => 'nullable|date_format:Y-m-d',
            'notes' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,png,jpg,pdf,docx,doc,xlsx,heic|max:10240',
            'orderProducts' => 'nullable|array',
            'orderProducts.*.type_of_product_id' => 'required_with:orderProducts|integer|exists:type_of_products,id',
            'orderProducts.*.product_category_id' => 'required_with:orderProducts|integer|exists:product_categories,id',
            'orderProducts.*.product_config_id' => 'required_with:orderProducts|integer|exists:product_configs,id',
            'orderProducts.*.width' => 'nullable|numeric',
            'orderProducts.*.height' => 'nullable|numeric',
            'orderProducts.*.qty' => 'required_with:orderProducts|numeric',
            'orderProducts.*.unit_price' => 'required_with:orderProducts|numeric',
            'orderProducts.*.total_price' => 'required_with:orderProducts|numeric',
            'orderProducts.*.unit_price_with_extraworks' => 'required_with:orderProducts|numeric',
            'orderProducts.*.total_price_with_extraworks' => 'required_with:orderProducts|numeric',
            'orderProducts.*.extra_work_price' => 'required_with:orderProducts|numeric',
            'orderProducts.*.extra_works' => 'nullable|array',
            'orderProducts.*.pivot_cost' => 'nullable|numeric',
        ];
    }
}
