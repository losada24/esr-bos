<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enum\GlassTypeEnum;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
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
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'name' => 'required|string|max:255',
            'order_number' => 'required|integer',
            'job_address' => 'required|string|max:255',
            'owners' => 'required|array',
            'owners.*' => 'required|integer|exists:users,id',
            'type_of_work_id' => 'required|integer|exists:type_of_works,id',
            'type_of_housing_id' => 'required|integer|exists:types_of_housing,id',
            'installation_teams' => 'required|array',
            'installation_teams.*' => 'required|integer|exists:installation_teams,id',
            'supervisor_id' => 'required|integer|exists:users,id',
            'travel_cost_id' => 'required|integer|exists:travel_costs,id',
            'duration_of_work_id' => 'required|integer|exists:duration_of_works,id',
            'additional_travel_costs' => 'nullable|numeric',
            'method_of_payment' => 'required|string|in:CASH,FINANCED',
            'service' => 'required|string|in:INSTALLATION,DELIVERY',
            'contract_signing_date' => 'required|date_format:Y-m-d',
            'payment_factory_date' => 'required|date_format:Y-m-d',
            'delivery_date' => 'nullable|date_format:Y-m-d',
            'entry_date' => 'nullable|date_format:Y-m-d',
            'installation_date' => 'nullable|date_format:Y-m-d',
            'city_permits' => 'boolean',
            'association_permits' => 'boolean',
            'equipment_rental' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,png,jpg,pdf,docx,doc,xlsx|max:5120',
            'orderProducts' => 'required|array',
            'orderProducts.*.type_of_product_id' => 'required|integer|exists:type_of_products,id',
            'orderProducts.*.product_category_id' => 'required|integer|exists:product_categories,id',
            'orderProducts.*.product_config_id' => 'required|integer|exists:product_configs,id',
            'orderProducts.*.width' => 'required|numeric',
            'orderProducts.*.height' => 'required|numeric',
            'orderProducts.*.qty' => 'required|numeric',
            'orderProducts.*.extra_works' => 'nullable|array',
        ];
    }
}
