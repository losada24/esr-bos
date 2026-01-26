<?php

namespace App\Http\Requests;

use App\Enum\ContactSourceEnum;
use App\Enum\ContactTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enum\States;

class StoreClientRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => [
              'required',
              'regex:/^\d{10}$/',
              'unique:clients,phone,' . ($this->client_id ?? 'null') . ',id'
            ],
            'address' => 'nullable|string|max:500',
            'appointment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'vip_clients' => 'boolean',
            'vip_notes' => 'nullable|string|max:1000',
            'contact_type' => [
              'nullable',
              'string',
              Rule::in(
                ContactTypeEnum::RESIDENTIAL_CONTACT->value,
                ContactTypeEnum::COMMERCIAL_CONTACT->value,
              )
            ],
            'source' => [
              'required',
              'string',
              Rule::in(
                 ContactSourceEnum::TIK_TOK->value,
            ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
            ContactSourceEnum::EXTERNAL_REFERAL->value,
            ContactSourceEnum::INTERNAL_REFERAL->value,
            ContactSourceEnum::SIGNS->value,
            ContactSourceEnum::WALK_IN->value,
            ContactSourceEnum::ESW_REFER->value,
            ContactSourceEnum::ESR_REFER->value,
            ContactSourceEnum::YOUTUBE->value,
            ContactSourceEnum::NEW_ORDER ->value,
            ContactSourceEnum::GOOGLE_ADS->value,
            ContactSourceEnum::SAME_AS_ORDER->value
              )
            ],
            'other_phone' => 'nullable|string|max:20',
            'secondary_email' => 'nullable|email|max:255',
            //'source' => 'nullable|string|max:255',
            'from_modal' => 'sometimes|boolean',
        ];
    }
}
