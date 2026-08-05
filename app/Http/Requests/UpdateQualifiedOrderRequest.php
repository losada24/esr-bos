<?php

namespace App\Http\Requests;

use App\Enum\ContactSourceEnum;
use App\Enum\FrameColorEnum;
use App\Enum\LanguageEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\ProductLineEnum;
use App\Enum\PaymentScheduleTypeEnum;
use App\Enum\PlaningDateSupervisorEnum;
use App\Enum\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Enum\TypeOfFinancing;
use App\Models\CompanyContact;
use App\Models\Order;
use App\Rules\ValidateOrderStatus;
use App\Support\PaymentScheduleTemplates;
use Illuminate\Validation\Rule;

class UpdateQualifiedOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['method_of_payment', 'type_of_financing', 'payment_schedule_type', 'down_payment', 'service'] as $field) {
            if ($this->has($field) && trim((string) $this->input($field)) === '') {
                $this->merge([$field => null]);
            }
        }

        $customSchedule = $this->input('custom_schedule', []);
        if ($this->input('payment_schedule_type') !== PaymentScheduleTypeEnum::CUSTOMIZED->value) {
            $this->merge(['custom_schedule' => []]);
        } elseif (is_array($customSchedule)) {
            $this->merge([
                'custom_schedule' => collect($customSchedule)
                    ->filter(fn ($item) => trim((string) ($item['label'] ?? '')) !== '' || trim((string) ($item['amount'] ?? '')) !== '')
                    ->values()
                    ->all(),
            ]);
        }
    }

    private function isEsrProcessOrder(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order && in_array($order->status, [
            OrderStatusEnum::DEALER_REQUEST->value,
            OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
            OrderStatusEnum::REVIEW->value,
            OrderStatusEnum::ACCOUNT_RECEIPT->value,
            OrderStatusEnum::PLANNED->value,
            OrderStatusEnum::PRODUCTION->value,
            OrderStatusEnum::PRODUCTION_SERVICES->value,
            OrderStatusEnum::PRE_COORDINATION_ACCOUNTING->value,
            OrderStatusEnum::PENDING_MAT_REYLOS->value,
            OrderStatusEnum::PENDING_MATERIALS->value,
            OrderStatusEnum::PENDING_MATERIALS_EWS->value,
            OrderStatusEnum::MATERIAL_ORDER_COMPLETED->value,
            OrderStatusEnum::MATERIAL_ORDER_COMPLETED_FINANCED->value,
            OrderStatusEnum::STORAGE_MATERIAL->value,
            OrderStatusEnum::MATERIALS_PICK_UP_OR_DELIVERED->value,
            OrderStatusEnum::PENDING_PAYMENT->value,
            OrderStatusEnum::COMPLETE->value,
            OrderStatusEnum::LOST->value,
        ], true);
    }

    private function canEditPaymentInformation(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            RoleEnum::ADMIN->value,
            RoleEnum::ACCOUNT_MANAGER->value,
            RoleEnum::ACCOUNTING->value,
            RoleEnum::OWNER_ADMIN->value,
        ]);
    }

    private function usesCompanyContacts(): bool
    {
        $orderType = $this->input('order_type');

        return $orderType === OrderTypeEnum::COMMERCIAL->value
            || ($this->isEsrProcessOrder() && $orderType === OrderTypeEnum::RESIDENTIAL->value);
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
           // 'client_id' => 'nullable|integer|exists:clients,id',
            'name' => 'required|string|max:255',
            'client_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->usesCompanyContacts()),
                'integer',
                'exists:clients,id',
            ],
            // 'last_name' => 'required|string|max:255',
            'order_type' => [
            'required',
            'string',
              Rule::in(
                OrderTypeEnum::RESIDENTIAL->value,
                OrderTypeEnum::COMMERCIAL->value,
              )
            ],
            'product_line' => ['nullable', 'string', Rule::enum(ProductLineEnum::class)],
           /* 'status' =>  [
            'nullable',
            'string',
              Rule::in(
                OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
                OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
                OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
                OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
                OrderStatusEnum::QUALIFIED->value,
              )
            ],*/
            'notes' => 'nullable|string|max:1000',
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
            'invoice_number' => 'nullable|string|max:255',
            'project_amount' => ['nullable', 'numeric', 'min:0'],
            'service' => [
                'nullable',
                'string',
                $this->isEsrProcessOrder()
                    ? Rule::in([
                        ServiceEnum::DELIVERY->value,
                        ServiceEnum::PICKUP->value,
                    ])
                    : Rule::enum(ServiceEnum::class),
            ],
            'esr_design' => ['nullable', 'boolean'],
            'esr_express' => ['nullable', 'boolean'],
            'esr_reylos_glass' => ['nullable', 'boolean'],
            'esr_service' => ['nullable', 'boolean'],
            'method_of_payment' => [
                'nullable',
                'string',
                Rule::in($this->isEsrProcessOrder()
                    ? [
                        MethodOfPayment::CASH->value,
                        MethodOfPayment::FINANCED->value,
                        MethodOfPayment::FINANCEDCASH->value,
                        MethodOfPayment::NOTPAYMENT->value,
                    ]
                    : [
                        MethodOfPayment::CASH->value,
                        MethodOfPayment::FINANCED->value,
                        MethodOfPayment::FINANCEDCASH->value,
                        MethodOfPayment::AIA->value,
                    ])
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
                )
            ],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'payment_schedule_type' => [
                'nullable',
                Rule::in(PaymentScheduleTemplates::types()),
            ],
            'custom_schedule' => ['nullable', 'array', 'max:6'],
            'custom_schedule.*.label' => ['required_with:custom_schedule', 'string', 'max:255'],
            'custom_schedule.*.amount' => ['required_with:custom_schedule', 'numeric', 'min:0.01'],
            'change_order_enabled' => 'boolean',
            'change_order_amount' => [
                'nullable',
                'numeric',
                Rule::requiredIf(fn () => filter_var($this->input('change_order_enabled'), FILTER_VALIDATE_BOOLEAN)),
            ],
            'change_order_note' => 'nullable|string|max:2000',
           
            // Solo obligatoria en COMMERCIAL
            'company_contact_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->usesCompanyContacts()),
                'integer',
                'exists:company_contacts,id',
            ],
            'company_source_id' => [
                'nullable',
                Rule::requiredIf(fn () => !$this->isEsrProcessOrder() && $this->input('order_type') === OrderTypeEnum::COMMERCIAL->value),
                'integer',
                'exists:sources,id',
            ],
            // Company asociadas (opcionales)
            'associate_company_contact_id_1' => ['nullable','integer','exists:company_contacts,id'],
            'associate_company_contact_id_2' => ['nullable','integer','exists:company_contacts,id'],
            'associate_company_contact_id_3' => ['nullable','integer','exists:company_contacts,id'],
            'associate_company_contact_id_4' => ['nullable','integer','exists:company_contacts,id'],

            // Client asociado requerido si hay company asociada
            'associate_client_id_1' => ['nullable','integer','exists:clients,id','required_with:associate_company_contact_id_1'],
            'associate_client_id_2' => ['nullable','integer','exists:clients,id','required_with:associate_company_contact_id_2'],  
            'associate_source_id_1' => [
                'nullable',
                'integer',
                'exists:sources,id',
                Rule::requiredIf(fn () => !$this->isEsrProcessOrder() && $this->filled('associate_company_contact_id_1')),
            ],
            'associate_source_id_2' => [
                'nullable',
                'integer',
                'exists:sources,id',
                Rule::requiredIf(fn () => !$this->isEsrProcessOrder() && $this->filled('associate_company_contact_id_2')),
            ],
            'client_email_selection' => ['required', 'string', 'max:255'],
            'hoa' => ['nullable', 'boolean'],
            'force_duplicate' => ['nullable', 'boolean'],
            'owner_ids' => ['nullable', 'array'],
            'owner_ids.*' => ['integer', 'exists:users,id'],
            'language' => [
              Rule::requiredIf(fn () => !$this->isEsrProcessOrder()),
              'nullable',
              'string',
              Rule::in(array_map(
                static fn (LanguageEnum $language) => $language->value,
                LanguageEnum::cases()
              ))
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');
            if (!$order instanceof Order) {
                return;
            }
            $isEsrProcessOrder = $this->isEsrProcessOrder();

            $existingSchedule = $order->paymentSchedule()->with('installments')->first();
            $hasRecordedPayments = $existingSchedule
                ? $existingSchedule->installments()->whereHas('movements')->exists()
                : false;
            $hasReachedContractSigned = $order->hasReachedContractSigned();

            $touchesProjectAmount = $this->exists('project_amount');
            $touchesPaymentConfiguration = $this->exists('method_of_payment')
                || $this->exists('type_of_financing')
                || $this->exists('down_payment')
                || $this->exists('payment_schedule_type')
                || $this->exists('custom_schedule');
            $touchesChangeOrder = $this->exists('change_order_enabled')
                || $this->exists('change_order_amount')
                || $this->exists('change_order_note');
            $touchesPrivilegedPaymentInformation = $touchesPaymentConfiguration || $touchesChangeOrder;

            if (!$isEsrProcessOrder && ($touchesPrivilegedPaymentInformation || ($touchesProjectAmount && $hasReachedContractSigned)) && !$this->canEditPaymentInformation()) {
                $validator->errors()->add('method_of_payment', 'You are not allowed to update Payment Information.');
                return;
            }

            if (!$isEsrProcessOrder && !$hasReachedContractSigned && $touchesPrivilegedPaymentInformation) {
                $validator->errors()->add('method_of_payment', 'Before CONTRACT SIGNED BY CLIENT, only Project Amount can be updated.');
                return;
            }

            $hasAssignedPaymentConfiguration = !empty((string) $order->method_of_payment)
                && !empty((string) ($existingSchedule?->schedule_type ?? ''));
            $isOwnerRole = $this->user()?->hasRole(RoleEnum::OWNER->value) ?? false;
            if (!$hasReachedContractSigned && $touchesProjectAmount && $isOwnerRole && $hasAssignedPaymentConfiguration) {
                $currentAmount = (float) ($order->project_amount ?? 0);
                $requestedAmount = (float) ($this->input('project_amount') ?? $currentAmount);
                if (abs($requestedAmount - $currentAmount) > 0.01) {
                    $validator->errors()->add('project_amount', 'Owner cannot edit Project Amount before CONTRACT SIGNED BY CLIENT when payment method and payment schedule are already assigned.');
                }
            }

            if ($this->usesCompanyContacts()) {
                $requestedCompanyIds = collect([
                    $this->input('company_contact_id'),
                    $this->input('associate_company_contact_id_1'),
                    $this->input('associate_company_contact_id_2'),
                ])
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($requestedCompanyIds->isNotEmpty()) {
                    $visibleCompanyCount = CompanyContact::visibleTo($this->user())
                        ->whereIn('id', $requestedCompanyIds)
                        ->count();

                    if ($visibleCompanyCount !== $requestedCompanyIds->count()) {
                        $validator->errors()->add('company_contact_id', 'You can only use companies associated with your owner account.');
                    }
                }
            }

            $changeOrderEnabled = filter_var($this->input('change_order_enabled'), FILTER_VALIDATE_BOOLEAN);
            if ($changeOrderEnabled && !$hasReachedContractSigned && !$isEsrProcessOrder) {
                $validator->errors()->add('change_order_enabled', 'Change Order is available only after CONTRACT SIGNED BY CLIENT.');
            }

            $projectAmountRaw = $this->exists('project_amount')
                ? $this->input('project_amount')
                : $order->project_amount;
            $projectAmount = (float) ($projectAmountRaw ?? 0);

            $methodOfPayment = (string) $this->input('method_of_payment', (string) $order->method_of_payment);
            $isCash = $methodOfPayment === MethodOfPayment::CASH->value;
            $isCashAndFinanced = $methodOfPayment === MethodOfPayment::FINANCEDCASH->value;
            $isSchedulePaymentMethod = $isCash || $isCashAndFinanced;
            $hasScheduleTypeInput = $this->exists('payment_schedule_type');
            $scheduleType = $hasScheduleTypeInput
                ? (string) $this->input('payment_schedule_type')
                : (string) ($existingSchedule?->schedule_type ?? '');
            $downPaymentRaw = $this->exists('down_payment')
                ? $this->input('down_payment')
                : $order->down_payment;
            $cashAmount = (float) ($downPaymentRaw ?? 0);

            if ($touchesPaymentConfiguration && $methodOfPayment === '') {
                $validator->errors()->add('method_of_payment', 'Select a payment method.');
            }

            if ($touchesPaymentConfiguration && $isSchedulePaymentMethod && $scheduleType === '') {
                $validator->errors()->add('payment_schedule_type', 'Select a payment schedule.');
            }

            if ($touchesPaymentConfiguration && $isCashAndFinanced) {
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

            if ($touchesPaymentConfiguration && $isSchedulePaymentMethod && $scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value && $this->exists('custom_schedule')) {
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

            if (!$hasRecordedPayments) {
                return;
            }

            if ($this->exists('project_amount')) {
                $currentAmount = (float) ($order->project_amount ?? 0);
                if (abs($projectAmount - $currentAmount) > 0.01) {
                    $validator->errors()->add('project_amount', 'Project amount cannot be changed after payments are recorded.');
                }
            }

            if (!$touchesPaymentConfiguration) {
                return;
            }

            $currentMethod = (string) $order->method_of_payment;
            if ($methodOfPayment !== $currentMethod) {
                $validator->errors()->add('method_of_payment', 'Project payment method cannot be changed after payments are recorded.');
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

            if (!$isSchedulePaymentMethod || $scheduleType !== (string) $existingSchedule?->schedule_type) {
                $validator->errors()->add('payment_schedule_type', 'Payment schedule cannot be changed after payments are recorded.');
                return;
            }

            if ($scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value && $this->exists('custom_schedule')) {
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

                $existingItems = $existingSchedule?->installments
                    ?->sortBy('position')
                    ->values()
                    ->map(fn ($item) => [
                        'label' => trim((string) $item->label),
                        'amount' => round((float) $item->amount, 2),
                    ])
                    ->all() ?? [];

                if ($incomingItems !== $existingItems) {
                    $validator->errors()->add('payment_schedule_type', 'Payment schedule cannot be changed after payments are recorded.');
                }
            }
        });
    }
}
