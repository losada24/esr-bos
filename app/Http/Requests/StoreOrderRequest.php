<?php

namespace App\Http\Requests;

use App\Enum\FrameColorEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentScheduleTypeEnum;
use App\Enum\PlaningDateSupervisorEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Enum\ServiceEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\ProductLineEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Enum\TypeOfFinancing;
use App\Support\PaymentScheduleTemplates;
use App\Models\Order;
use App\Rules\ValidateOrderStatus;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $normalize = function (string $key) {
            $value = $this->input($key);
            if ($value === 0 || $value === '0' || $value === '') {
                $this->merge([$key => null]);
            }
        };

        $normalize('client_id');
        $normalize('type_of_work_id');
        $normalize('type_of_housing_id');
        $normalize('travel_cost_id');
        $normalize('duration_of_work_id');
        $normalize('parent_order_id');

        if ($this->input('order_type') === '') {
            $this->merge(['order_type' => null]);
        }
        if ($this->input('payment_schedule_type') === '') {
            $this->merge(['payment_schedule_type' => null]);
        }
        if ($this->input('down_payment') === '') {
            $this->merge(['down_payment' => null]);
        }

        $orderProducts = $this->input('orderProducts');
        if (is_array($orderProducts)) {
            foreach ($orderProducts as $index => $product) {
                $typeOfWorkId = $product['type_of_work_id'] ?? null;
                if ($typeOfWorkId === 0 || $typeOfWorkId === '0' || $typeOfWorkId === '') {
                    $orderProducts[$index]['type_of_work_id'] = null;
                }
            }

            $this->merge(['orderProducts' => $orderProducts]);
        }
    }

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
            'client_id' => 'nullable|integer|exists:clients,id',
            'client_name' => 'required|string|max:255',
            // 'last_name' => 'required|string|max:255',
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
            'client_email_selection' => 'nullable|string|max:255',
            'is_send_email' => 'boolean',
            'is_new_travel_cost' => 'boolean',
            'vip_clients' => 'boolean',
            'vip_notes' => 'nullable|string|max:1000',
            'name' => 'required|string|max:255',
            'parent_order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')],
            //'order_number' => 'required|integer',
            'order_number' => 'required|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'job_address' => [
              'nullable',
              'string',
              'max:255',
              Rule::requiredIf(
                fn () => !in_array(
                  $this->input('service'),
                  [ServiceEnum::PICKUP->value, ServiceEnum::DELIVERY->value],
                  true
                )
              ),
            ],
            'owners' => 'required|array',
            'owners.*' => 'required|integer|exists:users,id',
            'type_of_work_id' => [
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
            'travel_cost_id' => [
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
            'new_travel_cost' => 'nullable|numeric',
            'cost_city_fee' => 'nullable|numeric',
            'project_amount' => 'nullable|numeric',
            'down_payment' => 'nullable|numeric',
            'initial_payment_percentage' => 'nullable|numeric',
            'payment_definition' => 'boolean',
            'change_order_enabled' => 'boolean',
            'change_order_amount' => [
              'nullable',
              'numeric',
              Rule::requiredIf(fn () => filter_var($this->input('change_order_enabled'), FILTER_VALIDATE_BOOLEAN)),
            ],
            'change_order_note' => 'nullable|string|max:2000',
            'method_of_payment' => [
              'required',
              'string',
              Rule::in(
                MethodOfPayment::CASH->value,
                MethodOfPayment::FINANCED->value,
                MethodOfPayment::FINANCEDCASH->value,
                MethodOfPayment::AIA->value
              )
            ],
            'payment_schedule_type' => [
              'nullable',
              Rule::requiredIf(fn () => in_array(
                $this->input('method_of_payment'),
                [MethodOfPayment::CASH->value, MethodOfPayment::FINANCEDCASH->value],
                true
              )),
              Rule::in(PaymentScheduleTemplates::types()),
            ],
            'custom_schedule' => ['nullable', 'array', 'max:6'],
            'custom_schedule.*.label' => ['required_with:custom_schedule', 'string', 'max:255'],
            'custom_schedule.*.amount' => ['required_with:custom_schedule', 'numeric', 'min:0.01'],
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
              )
            ],
        /*'frame_color' => [
            'nullable',
            Rule::when(
              fn($input) => $input->service == ServiceEnum::INSTALLATION->value
              , ['required', 'array', Rule::in([
                FrameColorEnum::WHITE->value,
                FrameColorEnum::BLACK->value,
                FrameColorEnum::BRONZE->value,
                FrameColorEnum::CLEAR_ANODIZED->value,
                FrameColorEnum::OTHERS->value
              ])]
            ),
          ],*/
            'service' => [
                'required',
                'string',
                Rule::in([
                  ServiceEnum::DELIVERY->value, 
                  ServiceEnum::INSTALLATION->value,
                  ServiceEnum::PICKUP->value,
                  ServiceEnum::SERVICE->value,
                ]),
            ],
            'order_type' => [
              'nullable',
              'string',
              Rule::in(
                OrderTypeEnum::RESIDENTIAL->value,
                OrderTypeEnum::COMMERCIAL->value,
                OrderTypeEnum::SUPPLY->value,
              )
            ],
            'product_line' => [
              'nullable',
              'string',
              Rule::enum(ProductLineEnum::class),
            ],
            'status' =>  [
            'required',
            'string',
              Rule::in(
                OrderStatusEnum::PLANNED->value,
                OrderStatusEnum::CONFIRMED->value,
                OrderStatusEnum::REVIEW->value,
              ),
              new ValidateOrderStatus
            ],
            'supervisor_payment_status' => [
              'nullable',
              'string',
              Rule::in(
                SupervisorPaymentStatusEnum::OPEN->value,
                SupervisorPaymentStatusEnum::PENDING->value,
                SupervisorPaymentStatusEnum::NO_PAID->value,
                SupervisorPaymentStatusEnum::CLOSED->value,
              )
            ],
            'execution_planing_date' => [
              'nullable',
              'numeric',
              Rule::in(
                PlaningDateSupervisorEnum::PROJECTS_WITHOUT_PERMISSIONS->value,
                PlaningDateSupervisorEnum::PROJECTS_WITH_PERMISSIONS->value,
                PlaningDateSupervisorEnum::COMMERCIAL_PROJECTS->value,
              )
            ],
            'inspection_date' => 'nullable|date_format:Y-m-d',
             'contract_signing_date' => [
               Rule::when(
                  fn ($input) => $input->status === OrderStatusEnum::REVIEW->value,
                  ['nullable'],
                  ['required']
              ),
              'date_format:Y-m-d',
          ],
          'payment_factory_date' => [
              Rule::when(
                  fn ($input) => $input->status === OrderStatusEnum::REVIEW->value,
                  ['nullable'],
                  ['required']
              ),
              'date_format:Y-m-d',
          ],
          'eta_date' => [
            Rule::when(
              fn ($input) => $input->status === OrderStatusEnum::REVIEW->value,
              ['nullable'],
              ['required']
            ),
            'date_format:Y-m-d',
          ],
            // 'eta_date' => 'nullable|date_format:Y-m-d',
            'installation_end_date' => 'nullable|date_format:Y-m-d',
            //'contract_signing_date' => 'nullable|date_format:Y-m-d',
            // 'payment_factory_date' => 'nullable|date_format:Y-m-d',
             'entry_date' => [
            Rule::when(
              fn ($input) => $input->status === OrderStatusEnum::REVIEW->value,
              ['nullable'],
              ['required']
            ),
            'date_format:Y-m-d',
          ],
            'delivery_date' => 'nullable|date_format:Y-m-d',
            // 'entry_date' => 'nullable|date_format:Y-m-d',
            'installation_date' => 'nullable|date_format:Y-m-d',
            'area' => 'nullable|numeric',
            'city_permits' => 'boolean',
            'city' => 'nullable|string|max:100',
            'job_state' => 'nullable|string|max:100',
            'job_zip' => 'nullable|string|max:100',
            'association_permits' => 'boolean',
            'equipment_rental' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'work_team_notes' => 'nullable|string|max:2000',
            'supervisor_commissions' => 'nullable|numeric',
            'supervisor_payment_percentage' => 'nullable|numeric',
            'supervisor_payment_date' => 'nullable|date_format:Y-m-d',
            'finish_date' => 'nullable|date_format:Y-m-d',
            'final_inspection_date' => 'nullable|date_format:Y-m-d',
            'complete_date' => 'nullable|date_format:Y-m-d',
            'inspection_date' => 'nullable|date_format:Y-m-d',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,png,jpg,pdf,docx,doc,xlsx,heic|max:10240',
            'orderProducts' => 'required|array',
            'orderProducts.*.type_of_product_id' => 'required|integer|exists:type_of_products,id',
            'orderProducts.*.product_category_id' => 'required|integer|exists:product_categories,id',
            'orderProducts.*.product_config_id' => 'required|integer|exists:product_configs,id',
            'orderProducts.*.type_of_work_id' => 'nullable|integer|exists:type_of_works,id',
            'orderProducts.*.width' => 'nullable|numeric',
            'orderProducts.*.height' => 'nullable|numeric',
            'orderProducts.*.qty' => 'required|numeric',
            'orderProducts.*.unit_price' => 'required|numeric',
            'orderProducts.*.total_price' => 'required|numeric',
            'orderProducts.*.unit_price_with_extraworks' => 'required|numeric',
            'orderProducts.*.total_price_with_extraworks' => 'required|numeric',
            'orderProducts.*.extra_work_price' => 'required|numeric',
            'orderProducts.*.extra_works' => 'nullable|array',
            'orderProducts.*.pivot_cost' => 'nullable|numeric',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isCash = $this->input('method_of_payment') === MethodOfPayment::CASH->value;
            $isCashAndFinanced = $this->input('method_of_payment') === MethodOfPayment::FINANCEDCASH->value;
            $scheduleType = (string) $this->input('payment_schedule_type');
            $cashAmount = (float) $this->input('down_payment', 0);
            $projectAmount = (float) $this->input('project_amount', 0);

            if ($isCashAndFinanced) {
                if ($this->input('down_payment') === null) {
                    $validator->errors()->add('down_payment', 'Cash amount is required for CASH AND FINANCED.');
                } elseif ($cashAmount <= 0) {
                    $validator->errors()->add('down_payment', 'Cash amount must be greater than 0.');
                } elseif ($projectAmount > 0 && $cashAmount >= $projectAmount) {
                    $validator->errors()->add('down_payment', 'Cash amount must be less than project amount.');
                }

                if ($scheduleType !== PaymentScheduleTypeEnum::CUSTOMIZED->value) {
                    $validator->errors()->add('payment_schedule_type', 'CASH AND FINANCED requires CUSTOMIZED payment schedule.');
                }
            }

            if ((!$isCash && !$isCashAndFinanced) || $scheduleType !== PaymentScheduleTypeEnum::CUSTOMIZED->value) {
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

            $targetAmount = $isCashAndFinanced ? $cashAmount : $projectAmount;
            if ($targetAmount > 0 && abs($total - $targetAmount) > 0.01) {
                $validator->errors()->add(
                    'custom_schedule',
                    $isCashAndFinanced
                        ? 'Custom payments must total the cash amount.'
                        : 'Custom payments must total the project amount.'
                );
            }
        });
    }
}
