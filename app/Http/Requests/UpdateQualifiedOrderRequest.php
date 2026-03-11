<?php

namespace App\Http\Requests;

use App\Enum\ContactSourceEnum;
use App\Enum\FrameColorEnum;
use App\Enum\LanguageEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\PaymentScheduleTypeEnum;
use App\Enum\PlaningDateSupervisorEnum;
use App\Enum\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Enum\TypeOfFinancing;
use App\Models\Order;
use App\Rules\ValidateOrderStatus;
use App\Support\PaymentScheduleTemplates;
use Illuminate\Validation\Rule;

class UpdateQualifiedOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['method_of_payment', 'type_of_financing', 'payment_schedule_type', 'down_payment'] as $field) {
            if ($this->has($field) && trim((string) $this->input($field)) === '') {
                $this->merge([$field => null]);
            }
        }
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
            'client_id' => ['nullable', 'required_if:order_type,COMMERCIAL', 'integer', 'exists:clients,id'],
            // 'last_name' => 'required|string|max:255',
            'order_type' => [
            'required',
            'string',
              Rule::in(
                OrderTypeEnum::RESIDENTIAL->value,
                OrderTypeEnum::COMMERCIAL->value,
              )
            ],
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
            'project_amount' => ['nullable', 'numeric', 'min:0'],
            'method_of_payment' => [
                'nullable',
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
            'company_contact_id' => [  'nullable','required_if:order_type,COMMERCIAL', 'integer', 'exists:company_contacts,id'],
            'company_source_id' => ['nullable', 'required_if:order_type,COMMERCIAL', 'integer', 'exists:sources,id'],
            // Company asociadas (opcionales)
            'associate_company_contact_id_1' => ['nullable','integer','exists:company_contacts,id'],
            'associate_company_contact_id_2' => ['nullable','integer','exists:company_contacts,id'],

            // Client asociado requerido si hay company asociada
            'associate_client_id_1' => ['nullable','integer','exists:clients,id','required_with:associate_company_contact_id_1'],
            'associate_client_id_2' => ['nullable','integer','exists:clients,id','required_with:associate_company_contact_id_2'],  
            'associate_source_id_1' => ['nullable','integer','exists:sources,id','required_with:associate_company_contact_id_1'],
            'associate_source_id_2' => ['nullable','integer','exists:sources,id','required_with:associate_company_contact_id_2'],
            'hoa' => ['nullable', 'boolean'],
            'force_duplicate' => ['nullable', 'boolean'],
            'language' => [
              'required',
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

            if (($touchesPrivilegedPaymentInformation || ($touchesProjectAmount && $hasReachedContractSigned)) && !$this->canEditPaymentInformation()) {
                $validator->errors()->add('method_of_payment', 'You are not allowed to update Payment Information.');
                return;
            }

            if (!$hasReachedContractSigned && $touchesPrivilegedPaymentInformation) {
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

            $changeOrderEnabled = filter_var($this->input('change_order_enabled'), FILTER_VALIDATE_BOOLEAN);
            if ($changeOrderEnabled && !$hasReachedContractSigned) {
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
