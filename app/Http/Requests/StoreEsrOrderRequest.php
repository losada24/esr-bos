<?php

namespace App\Http\Requests;

use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\PaymentScheduleTypeEnum;
use App\Enum\ProductLineEnum;
use App\Enum\ServiceEnum;
use App\Enum\TypeOfFinancing;
use App\Support\PaymentScheduleTemplates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEsrOrderRequest extends FormRequest
{
    private function allowedPaymentMethods(): array
    {
        return array_values(array_filter(
            array_map(fn (MethodOfPayment $method) => $method->value, MethodOfPayment::cases()),
            fn (string $method) => !in_array($method, [
                MethodOfPayment::AIA->value,
                MethodOfPayment::ZELLE->value,
                MethodOfPayment::CHECK->value,
            ], true)
        ));
    }

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

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_type' => ['required', Rule::in([OrderTypeEnum::COMMERCIAL->value])],
            'product_line' => ['required', Rule::enum(ProductLineEnum::class)],
            'name' => ['required', 'string', 'max:255'],
            'job_address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
            'job_state' => ['required', 'string', 'max:255'],
            'job_zip' => ['required', 'string', 'max:20'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'company_contact_id' => ['required', 'integer', 'exists:company_contacts,id'],
            'associate_company_contact_id_1' => ['nullable', 'integer', 'exists:company_contacts,id'],
            'associate_client_id_1' => ['nullable', 'integer', 'exists:clients,id', 'required_with:associate_company_contact_id_1'],
            'associate_company_contact_id_2' => ['nullable', 'integer', 'exists:company_contacts,id'],
            'associate_client_id_2' => ['nullable', 'integer', 'exists:clients,id', 'required_with:associate_company_contact_id_2'],
            'client_email_selection' => ['required', 'string', 'max:255'],
            'owner_ids' => ['required', 'array', 'min:1'],
            'owner_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'status' => [
                'required',
                Rule::in([
                    OrderStatusEnum::DEALER_REQUEST->value,
                    OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
                    OrderStatusEnum::REVIEW->value,
                ]),
            ],
            'order_number' => ['required', 'string', 'max:255'],
            'project_amount' => ['required', 'numeric', 'min:0'],
            'service' => ['nullable', 'string', Rule::in([
                ServiceEnum::DELIVERY->value,
                ServiceEnum::PICKUP->value,
            ])],
            'esr_design' => ['nullable', 'boolean'],
            'esr_express' => ['nullable', 'boolean'],
            'esr_reylos_glass' => ['nullable', 'boolean'],
            'esr_service' => ['nullable', 'boolean'],
            'method_of_payment' => [
                'nullable',
                'string',
                Rule::in($this->allowedPaymentMethods()),
            ],
            'type_of_financing' => [
                'nullable',
                'string',
                Rule::in(array_map(fn (TypeOfFinancing $financing) => $financing->value, TypeOfFinancing::cases())),
            ],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'payment_schedule_type' => [
                'nullable',
                Rule::in(PaymentScheduleTemplates::types()),
            ],
            'custom_schedule' => ['nullable', 'array', 'max:6'],
            'custom_schedule.*.label' => ['required_with:custom_schedule', 'string', 'max:255'],
            'custom_schedule.*.amount' => ['required_with:custom_schedule', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $methodOfPayment = (string) $this->input('method_of_payment', '');
            $scheduleType = (string) $this->input('payment_schedule_type', '');
            $isCash = $methodOfPayment === MethodOfPayment::CASH->value;
            $isCashAndFinanced = $methodOfPayment === MethodOfPayment::FINANCEDCASH->value;
            $requiresSchedule = $isCash || $isCashAndFinanced;

            if (!$requiresSchedule) {
                return;
            }

            if ($scheduleType === '') {
                $validator->errors()->add('payment_schedule_type', 'Select a payment schedule.');
                return;
            }

            if ($isCashAndFinanced) {
                $downPaymentRaw = $this->input('down_payment');
                $projectAmount = (float) ($this->input('project_amount') ?? 0);
                $downPayment = (float) ($downPaymentRaw ?? 0);

                if ($downPaymentRaw === null || $downPaymentRaw === '') {
                    $validator->errors()->add('down_payment', 'Cash amount is required for CASH AND FINANCED.');
                } elseif ($downPayment <= 0) {
                    $validator->errors()->add('down_payment', 'Cash amount must be greater than 0.');
                } elseif ($projectAmount > 0 && $downPayment >= $projectAmount) {
                    $validator->errors()->add('down_payment', 'Cash amount must be less than project amount.');
                }

                if ($scheduleType !== PaymentScheduleTypeEnum::CUSTOMIZED->value) {
                    $validator->errors()->add('payment_schedule_type', 'CASH AND FINANCED requires CUSTOMIZED payment schedule.');
                }
            }

            if ($scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
                $customSchedule = $this->input('custom_schedule', []);
                if (!is_array($customSchedule) || count($customSchedule) === 0) {
                    $validator->errors()->add('custom_schedule', 'Add at least one custom payment.');
                    return;
                }

                $total = 0.0;
                foreach ($customSchedule as $item) {
                    $total += (float) ($item['amount'] ?? 0);
                }

                $targetAmount = $isCashAndFinanced
                    ? (float) ($this->input('down_payment') ?? 0)
                    : (float) ($this->input('project_amount') ?? 0);

                if ($targetAmount > 0 && abs($total - $targetAmount) > 0.01) {
                    $validator->errors()->add(
                        'custom_schedule',
                        $isCashAndFinanced
                            ? 'Custom payments must total the cash amount.'
                            : 'Custom payments must total the project amount.'
                    );
                }
            }
        });
    }
}
