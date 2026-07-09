<?php

namespace App\Http\Requests;

use App\Enum\ServiceControlClosureResultEnum;
use App\Enum\ServiceControlCreationSourceEnum;
use App\Enum\ServiceControlPriorityEnum;
use App\Enum\ServiceControlRequestOriginEnum;
use App\Enum\ServiceControlSourceEnum;
use App\Enum\ServiceControlStatusEnum;
use App\Enum\ServiceControlTypeEnum;
use App\Enum\AreaEnum;
use App\Enum\BmInvoiceStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $newClient = $this->input('new_client', []);

        if (is_array($newClient) && array_key_exists('phone', $newClient)) {
            $newClient['phone'] = preg_replace('/\D+/', '', (string) $newClient['phone']);
        }

        $serviceType = $this->input('service_type');

        $this->merge([
            'new_client' => $newClient,
            'service_type' => is_string($serviceType) && $serviceType !== '' ? [$serviceType] : $serviceType,
            'service_source' => $this->input('service_source') ?: ServiceControlSourceEnum::ESR->value,
            'request_origin' => $this->input('request_origin') ?: ServiceControlRequestOriginEnum::SERVICE->value,
        ]);
    }

    public function rules(): array
    {
        $requiresStandaloneClient = fn () => ! $this->filled('order_id') && ! $this->filled('client_id');
        $usesExistingClient = fn () => $this->filled('order_id') || $this->filled('client_id');

        return [
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'new_client.name' => [Rule::excludeIf($usesExistingClient), 'nullable', Rule::requiredIf($requiresStandaloneClient), 'string', 'max:255'],
            'new_client.phone' => [
                Rule::excludeIf($usesExistingClient),
                'nullable',
                Rule::requiredIf($requiresStandaloneClient),
                'regex:/^\d{10}$/',
                Rule::unique('clients', 'phone'),
            ],
            'new_client.email' => [Rule::excludeIf($usesExistingClient), 'nullable', 'email', 'max:255'],
            'new_client.other_phone' => [Rule::excludeIf($usesExistingClient), 'nullable', 'string', 'max:20'],
            'new_client.secondary_email' => [Rule::excludeIf($usesExistingClient), 'nullable', 'email', 'max:255'],
            'service_name' => ['required', 'string', 'max:255'],
            'service_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('service_controls', 'service_id')->whereNull('deleted_at'),
            ],
            'external_order_id' => ['nullable', 'string', 'max:255'],
            'is_bm' => ['boolean'],
            'service_source' => [Rule::excludeIf(fn () => $this->boolean('is_bm')), Rule::requiredIf(fn () => ! $this->boolean('is_bm')), 'string', Rule::in(array_column(ServiceControlSourceEnum::cases(), 'value'))],
            'creation_source' => ['nullable', 'string', Rule::in(array_column(ServiceControlCreationSourceEnum::cases(), 'value'))],
            'request_origin' => ['nullable', 'string', Rule::in(array_column(ServiceControlRequestOriginEnum::cases(), 'value'))],
            'service_type' => [Rule::excludeIf(fn () => $this->boolean('is_bm')), Rule::requiredIf(fn () => ! $this->boolean('is_bm')), 'array', 'min:1'],
            'service_type.*' => ['string', Rule::in(array_column(ServiceControlTypeEnum::cases(), 'value'))],
            'description' => ['nullable', 'string'],
            'requires_part' => ['boolean'],
            'requested_parts' => ['boolean'],
            'parts_available' => ['boolean'],
            'service_status' => ['nullable', Rule::requiredIf(fn () => ! $this->boolean('is_bm')), 'string', Rule::in(array_column(ServiceControlStatusEnum::cases(), 'value'))],
            'priority' => ['nullable', Rule::requiredIf(fn () => ! $this->boolean('is_bm')), 'string', Rule::in(array_column(ServiceControlPriorityEnum::cases(), 'value'))],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'area' => ['nullable', 'string', Rule::in(array_column(AreaEnum::cases(), 'value'))],
            'requester_type' => ['nullable', 'string', Rule::in(['user', 'client'])],
            'requester_id' => ['nullable', 'integer', 'min:1'],
            'requester_role' => ['nullable', 'string', 'max:50'],
            'assignee_type' => ['nullable', 'string', Rule::in(['user', 'client'])],
            'assignee_id' => ['nullable', 'integer', 'min:1'],
            'assignee_role' => ['nullable', 'string', 'max:50'],
            'target_date' => ['nullable', 'date_format:Y-m-d'],
            'service_created_date' => ['nullable', 'date_format:Y-m-d'],
            'service_id_requested_date' => ['nullable', 'date_format:Y-m-d'],
            'eta_date' => ['nullable', 'date_format:Y-m-d'],
            'parts_received_date' => ['nullable', 'date_format:Y-m-d'],
            'part_delivered_date' => ['nullable', 'date_format:Y-m-d'],
            'scheduled_date' => ['nullable', 'date_format:Y-m-d'],
            'executed_date' => ['nullable', 'date_format:Y-m-d'],
            'closure_result' => [
                'nullable',
                'string',
                Rule::in(array_column(ServiceControlClosureResultEnum::cases(), 'value')),
            ],
            'observations' => ['nullable', 'string'],
            'bm_quantity' => ['nullable', Rule::requiredIf(fn () => $this->boolean('is_bm')), 'integer', 'min:1'],
            'bm_requested_date' => ['nullable', Rule::requiredIf(fn () => $this->boolean('is_bm')), 'date_format:Y-m-d'],
            'bm_picked_up_by' => ['nullable', 'string', 'max:255'],
            'bm_pickup_date' => ['nullable', 'date_format:Y-m-d'],
            'bm_invoice_number' => ['nullable', 'string', 'max:255'],
            'bm_invoice_status' => ['nullable', Rule::requiredIf(fn () => $this->boolean('is_bm')), 'string', Rule::in(array_column(BmInvoiceStatusEnum::cases(), 'value'))],
            'external_company_contact_id' => ['nullable', 'integer', 'exists:company_contacts,id'],
            'external_owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'external_owner_name' => ['nullable', 'string', 'max:255'],
            'external_owner_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
