<?php

namespace App\Http\Requests;

use App\Enum\ContactSourceEnum;
use App\Enum\FrameColorEnum;
use App\Enum\LanguageEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\ProductLineEnum;
use App\Enum\PlaningDateSupervisorEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Enum\TypeOfFinancing;
use App\Rules\ValidateOrderStatus;
use Illuminate\Validation\Rule;

class StoreQualifiedOrderRequest extends FormRequest
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
           // 'client_id' => 'nullable|integer|exists:clients,id',
            'name' => 'required|string|max:255',
            'client_id' => 'required|integer|exists:clients,id',
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
            'status' =>  [
            'nullable',
            'string',
              Rule::in(
                OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
                OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
                OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
                OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
                OrderStatusEnum::QUALIFIED->value,
              )
            ],
            'notes' => 'nullable|string|max:1000',
           
            // Solo obligatoria en COMMERCIAL
            'company_contact_id' => [  'nullable','required_if:order_type,COMMERCIAL', 'integer', 'exists:company_contacts,id'],
            'company_source_id' => ['nullable', 'required_if:order_type,COMMERCIAL', 'integer', 'exists:sources,id'],
            // Company asociadas (opcionales)
            'associate_company_contact_id_1' => ['nullable','integer','exists:company_contacts,id'],
            'associate_company_contact_id_2' => ['nullable','integer','exists:company_contacts,id'],
            'associate_company_contact_id_3' => ['nullable','integer','exists:company_contacts,id'],
            'associate_company_contact_id_4' => ['nullable','integer','exists:company_contacts,id'],

            // Client asociado requerido si hay company asociada
            'associate_client_id_1' => ['nullable','integer','exists:clients,id','required_with:associate_company_contact_id_1'],
            'associate_client_id_2' => ['nullable','integer','exists:clients,id','required_with:associate_company_contact_id_2'],
            'associate_client_id_3' => ['nullable','integer','exists:clients,id','required_with:associate_company_contact_id_3'],
            'associate_client_id_4' => ['nullable','integer','exists:clients,id','required_with:associate_company_contact_id_4'],
            'associate_source_id_1' => ['nullable', 'integer', 'exists:sources,id', 'required_with:associate_company_contact_id_1'],
            'associate_source_id_2' => ['nullable', 'integer', 'exists:sources,id', 'required_with:associate_company_contact_id_2'],
            'associate_source_id_3' => ['nullable', 'integer', 'exists:sources,id', 'required_with:associate_company_contact_id_3'],
            'associate_source_id_4' => ['nullable', 'integer', 'exists:sources,id', 'required_with:associate_company_contact_id_4'],
            'client_email_selection' => ['required', 'string', 'max:255'],
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
}
