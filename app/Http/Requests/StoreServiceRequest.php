<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentScheduleTypeEnum;
use App\Enum\ProductLineEnum;
use App\Enum\ServiceEnum;
use App\Enum\TypeOfFinancing;
use App\Support\PaymentScheduleTemplates;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $clientId = $this->input('client_id');
        if ($clientId === 0 || $clientId === '0' || $clientId === '') {
            $this->merge(['client_id' => null]);
        }

        if ($this->input('payment_schedule_type') === '') {
            $this->merge(['payment_schedule_type' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_id' => 'nullable|integer|exists:clients,id',
            'client_name' => 'required|string|max:255',
            //'phone' => 'required|string|max:255',
             'phone' => [
              'required',
              'regex:/^\d{10}$/',
              Rule::when(
                fn ($input) => empty($input->client_id),
                [Rule::unique('clients', 'phone')]
              )
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
            'product_line' => ['nullable', 'string', Rule::enum(ProductLineEnum::class)],
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
            'payment_schedule_type' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('method_of_payment') === MethodOfPayment::CASH->value),
                Rule::in(PaymentScheduleTemplates::types()),
            ],
            'custom_schedule' => ['nullable', 'array', 'max:6'],
            'custom_schedule.*.label' => ['required_with:custom_schedule', 'string', 'max:255'],
            'custom_schedule.*.amount' => ['required_with:custom_schedule', 'numeric', 'min:0.01'],
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
            'down_payment' => 'nullable|numeric',
            'change_order_enabled' => 'boolean',
            'change_order_amount' => [
                'nullable',
                'numeric',
                Rule::requiredIf(fn () => filter_var($this->input('change_order_enabled'), FILTER_VALIDATE_BOOLEAN)),
            ],
            'change_order_note' => 'nullable|string|max:2000',
            'entry_date' => 'nullable|date_format:Y-m-d',
            'delivery_date' => 'nullable|date_format:Y-m-d',
            'installation_date' => 'nullable|date_format:Y-m-d',
            'notes' => 'nullable|string|max:1000',
            'work_team_notes' => 'nullable|string|max:2000',
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isCash = $this->input('method_of_payment') === MethodOfPayment::CASH->value;
            $scheduleType = (string) $this->input('payment_schedule_type');

            if (!$isCash || $scheduleType !== PaymentScheduleTypeEnum::CUSTOMIZED->value) {
                return;
            }

            $customSchedule = $this->input('custom_schedule', []);
            if (!is_array($customSchedule) || count($customSchedule) === 0) {
                $validator->errors()->add('custom_schedule', 'Add at least one custom payment.');
                return;
            }

            $total = 0.0;
            foreach ($customSchedule as $item) {
                $total += (float) ($item['amount'] ?? 0);
            }

            $projectAmount = (float) $this->input('project_amount', 0);
            if (abs($total - $projectAmount) > 0.01) {
                $validator->errors()->add('custom_schedule', 'Custom payments must total the project amount.');
            }
        });
    }
}
