<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enum\FrameColorEnum;
use App\Enum\ServiceEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\PlaningDateSupervisorEnum;
use App\Enum\PaymentScheduleTypeEnum;
use App\Enum\RoleEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Enum\TypeOfFinancing;
use App\Enum\OrderTypeEnum;
use App\Enum\ProductLineEnum;
use App\Models\Client;
use App\Models\Order;
use App\Rules\ValidateOrderStatus;
use App\Support\PaymentScheduleTemplates;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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

    private function shouldValidateUniquePhone(): bool
    {
        $clientId = $this->input('client_id');
        if (empty($clientId)) {
            return true;
        }

        $currentPhone = Client::query()->where('id', $clientId)->value('phone');
        $incomingPhone = (string) ($this->input('phone') ?? '');

        $normalizedIncoming = preg_replace('/\D+/', '', $incomingPhone) ?? '';
        $normalizedCurrent = preg_replace('/\D+/', '', (string) $currentPhone) ?? '';

        return $normalizedIncoming !== $normalizedCurrent;
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
          'id' => 'required|exists:orders,id',
          'client_id' => 'nullable|integer|exists:clients,id',
          'client_name' => 'required|string|max:255',
          // 'last_name' => 'required|string|max:255',
          //'phone' => 'required|string|max:255',
           'phone' => [
              'required',
              'regex:/^\d{10}$/',
              Rule::when(
                fn () => $this->shouldValidateUniquePhone(),
                [Rule::unique('clients', 'phone')->ignore($this->input('client_id'))]
              )
            ],
          'phone_ext' => 'nullable|string|max:20',
          'email' => 'nullable|email|max:255',
          'client_email_selection' => 'nullable|string|max:255',
          'vip_clients' => 'boolean',
          'is_send_email' => 'boolean',
          'is_new_travel_cost' => 'boolean',
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
          'owners' => [
            Rule::when(
              fn ($input) => $input->service == ServiceEnum::SERVICE->value,
              ['nullable', 'array'],
              ['required', 'array']
            )
          ],
          'owners.*' => 'integer|exists:users,id',
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
          'installation_teams.*' => 'integer|exists:installation_teams,id',
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
          'new_travel_cost' => 'nullable|numeric',
          'cost_city_fee' => 'nullable|numeric',
          'project_amount' => 'nullable|numeric',
          'esr_cost' => [
            'nullable',
            'numeric',
          ],
          'down_payment' => 'nullable|numeric',
          'city' => 'nullable|string|max:100',
          'job_state' => 'nullable|string|max:100',
          'job_zip' => 'nullable|string|max:100',
          'initial_payment_percentage' => 'nullable|numeric',
          'payment_definition' => 'boolean',
          'change_order_enabled' => 'boolean',
          'change_order_amount' => [
            'nullable',
            'numeric',
            Rule::requiredIf(fn () => filter_var($this->input('change_order_enabled'), FILTER_VALIDATE_BOOLEAN)),
          ],
          'change_order_note' => 'nullable|string|max:2000',
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
          'payment_schedule_type' => [
            'nullable',
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
          'status' =>  [
            'required',
            'string',
            Rule::in(
              OrderStatusEnum::REVIEW->value,
              OrderStatusEnum::PLANNED->value,
              OrderStatusEnum::REPLANNED->value,
              OrderStatusEnum::CONFIRMED->value,
              OrderStatusEnum::EXECUTION->value,
              OrderStatusEnum::SUPERVISION->value,
              OrderStatusEnum::INSPECTION->value,
              OrderStatusEnum::SERVICE->value,
              OrderStatusEnum::FINISH->value,
              OrderStatusEnum::FINAL_INSPECTION->value,
              OrderStatusEnum::FINAL_COLLECT->value,
              OrderStatusEnum::ON_HOLD->value,
              OrderStatusEnum::DELIVERY_CONFIRMED->value,
              OrderStatusEnum::COMPLETE->value,
              OrderStatusEnum::RESCHEDULE->value,
              OrderStatusEnum::MATERIALS_RECEIVED->value,
              OrderStatusEnum::CANCELED->value,
            ),
            new ValidateOrderStatus
          ],
          'replanned_reasons' => [
            'nullable',
            'array',
            Rule::requiredIf(fn () => $this->input('status') === OrderStatusEnum::REPLANNED->value),
            'min:1',
          ],
          'replanned_reasons.*' => [
            'string',
            Rule::in(['CLIENT', 'PERMIT', 'MATERIALS']),
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
          //'contract_signing_date' => 'required|date_format:Y-m-d',
          'contract_signing_date' => [
           Rule::when(
                  fn ($input) => $input->status === OrderStatusEnum::REVIEW->value,
                  ['nullable'],
                  ['required']
              ),
              'date_format:Y-m-d',
          ],
          // 'payment_factory_date' => 'required|date_format:Y-m-d',
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
          // 'eta_date' => 'required|date_format:Y-m-d',
          'installation_end_date' => 'nullable|date_format:Y-m-d',
          'delivery_date' => 'nullable|date_format:Y-m-d',
          'entry_date' => [
            Rule::when(
              fn ($input) => $input->status === OrderStatusEnum::REVIEW->value,
              ['nullable'],
              ['required']
            ),
            'date_format:Y-m-d',
          ],
          // 'entry_date' => 'required|date_format:Y-m-d',
          'installation_date' => 'nullable|date_format:Y-m-d',
          'city_permits' => 'boolean',
          'association_permits' => 'boolean',
          'equipment_rental' => 'boolean',
          'notes' => 'nullable|string|max:2000',
          'work_team_notes' => 'nullable|string|max:2000',
          'supervisor_commissions' => 'nullable|numeric',
          'supervisor_payment_percentage' => 'nullable|numeric',
          'supervisor_payment_date' => 'nullable|date_format:Y-m-d',
          'finish_date' => 'nullable|date_format:Y-m-d',
          'final_inspection_date' => 'nullable|date_format:Y-m-d',
          'inspection_date' => 'nullable|date_format:Y-m-d',
          'material_received_date' => 'nullable|date_format:Y-m-d',
          'complete_date' => 'nullable|date_format:Y-m-d',
          'attachments' => 'nullable|array',
          'attachments.*' => 'file|mimes:jpeg,png,jpg,pdf,docx,doc,xlsx,heic|max:10240',
          'attachment_role_targets' => 'nullable|array',
          'attachment_role_targets.*' => 'nullable|array',
          'attachment_role_targets.*.*' => 'integer|exists:attachments,id',
          'orderProducts' => [
            Rule::when(
              fn ($input) => $input->service == ServiceEnum::SERVICE->value,
              ['nullable', 'array'],
              ['required', 'array']
            )
          ],
          'orderProducts.*.type_of_product_id' => 'required_with:orderProducts|integer|exists:type_of_products,id',
          'orderProducts.*.product_category_id' => 'required_with:orderProducts|integer|exists:product_categories,id',
          'orderProducts.*.product_config_id' => 'required_with:orderProducts|integer|exists:product_configs,id',
          'orderProducts.*.type_of_work_id' => 'nullable|integer|exists:type_of_works,id',
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
            $order = $this->route('order');
            if (!$order instanceof Order) {
                return;
            }

            $methodOfPayment = (string) $this->input('method_of_payment', (string) $order->method_of_payment);
            $isCash = $methodOfPayment === MethodOfPayment::CASH->value;
            $isCashAndFinanced = $methodOfPayment === MethodOfPayment::FINANCEDCASH->value;
            $isSchedulePaymentMethod = $isCash || $isCashAndFinanced;
            $hasScheduleTypeInput = $this->exists('payment_schedule_type');
            $hasCustomScheduleInput = $this->exists('custom_schedule');
            $existingSchedule = $order->paymentSchedule()->with('installments')->first();
            $scheduleType = $hasScheduleTypeInput
                ? (string) $this->input('payment_schedule_type')
                : (string) ($existingSchedule?->schedule_type ?? '');

            $projectAmountRaw = $this->exists('project_amount')
                ? $this->input('project_amount')
                : $order->project_amount;
            $projectAmount = (float) ($projectAmountRaw ?? 0);

            if ($this->exists('project_amount') && ($this->user()?->hasRole(RoleEnum::OWNER_ADMIN->value) ?? false)) {
                $currentAmount = (float) ($order->project_amount ?? 0);
                if (abs($projectAmount - $currentAmount) > 0.01) {
                    $validator->errors()->add('project_amount', 'Owner Admin cannot edit Project Amount.');
                    return;
                }
            }

            $downPaymentRaw = $this->exists('down_payment')
                ? $this->input('down_payment')
                : $order->down_payment;
            $cashAmount = (float) ($downPaymentRaw ?? 0);

            if ($isCashAndFinanced) {
                if ($downPaymentRaw === null || $downPaymentRaw === '') {
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

            if ($isSchedulePaymentMethod && $scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value && $hasCustomScheduleInput) {
                $customSchedule = $this->input('custom_schedule', []);
                if (!is_array($customSchedule) || count($customSchedule) === 0) {
                    $validator->errors()->add('custom_schedule', 'Add at least one custom payment.');
                } else {
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
                }
            }

            if (!$existingSchedule) {
                return;
            }

            $hasRecordedPayments = $existingSchedule->installments()->whereHas('movements')->exists();
            if (!$hasRecordedPayments) {
                return;
            }

            $currentMethod = (string) $order->method_of_payment;
            if ($methodOfPayment !== $currentMethod) {
                $validator->errors()->add('method_of_payment', 'Project payment method cannot be changed after payments are recorded.');
            }

            if ($this->exists('project_amount')) {
                $currentAmount = (float) ($order->project_amount ?? 0);
                if (abs($projectAmount - $currentAmount) > 0.01) {
                    $validator->errors()->add('project_amount', 'Project amount cannot be changed after payments are recorded.');
                }
            }

            if ($this->exists('down_payment')) {
                $currentDownPaymentRaw = $order->down_payment;
                $requestedDownPaymentRaw = $this->input('down_payment');
                $currentDownPayment = $currentDownPaymentRaw === null ? null : (float) $currentDownPaymentRaw;
                $requestedDownPayment = $requestedDownPaymentRaw === null || $requestedDownPaymentRaw === ''
                    ? null
                    : (float) $requestedDownPaymentRaw;

                $downPaymentChanged = $currentDownPayment === null
                    ? $requestedDownPayment !== null
                    : ($requestedDownPayment === null || abs($requestedDownPayment - $currentDownPayment) > 0.01);

                if ($downPaymentChanged) {
                    $validator->errors()->add('down_payment', 'Cash amount cannot be changed after payments are recorded.');
                }
            }

            if (!$isSchedulePaymentMethod || $scheduleType !== (string) $existingSchedule->schedule_type) {
                $validator->errors()->add('payment_schedule_type', 'Payment schedule cannot be changed after payments are recorded.');
                return;
            }

            if ($scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value && $hasCustomScheduleInput) {
                $incomingItems = collect($this->input('custom_schedule', []))
                    ->map(function ($item) {
                        return [
                            'label' => trim((string) ($item['label'] ?? '')),
                            'amount' => round((float) ($item['amount'] ?? 0), 2),
                        ];
                    })
                    ->filter(fn ($item) => $item['label'] !== '')
                    ->values()
                    ->all();

                $existingItems = $existingSchedule->installments
                    ->sortBy('position')
                    ->values()
                    ->map(fn ($item) => [
                        'label' => trim((string) $item->label),
                        'amount' => round((float) $item->amount, 2),
                    ])
                    ->all();

                if ($incomingItems !== $existingItems) {
                    $validator->errors()->add('payment_schedule_type', 'Payment schedule cannot be changed after payments are recorded.');
                }
            }
        });
    }
}
