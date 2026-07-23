<?php

namespace App\Http\Requests;

use App\Enum\ContactSourceEnum;
use App\Enum\ContactTypeEnum;
use App\Enum\OrderTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
              Rule::requiredIf(fn () => $this->input('order_type') !== OrderTypeEnum::COMMERCIAL->value),
              'nullable',
              'regex:/^\d{10}$/',
              Rule::unique('clients', 'phone')->ignore($this->client_id)
            ],
            'phone_ext' => 'nullable|string|max:20',
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
            ContactSourceEnum::SAME_AS_ORDER->value, 
            ContactSourceEnum::DIRECT_CALL->value,
            ContactSourceEnum::CANVASS->value,
            ContactSourceEnum::TRUCK_LED->value,
            ContactSourceEnum::COSTCO->value,
              )
            ],
            'other_phone' => 'nullable|string|max:20',
            'secondary_email' => 'nullable|email|max:255',
            'refer_name' => 'nullable|string|max:255',
            'refer_phone' => 'nullable|string|max:50',
            'refer_email' => 'nullable|email|max:255',
            'referral_id' => 'nullable|integer|exists:referrals,id',
            'referrer_client_id' => 'nullable|integer|exists:clients,id',
            'referrer_user_id' => 'nullable|integer|exists:users,id',
            'from_modal' => 'sometimes|boolean',
            'force_create' => 'sometimes|boolean',
        ];
    }
}
