<?php

namespace App\Http\Requests;

use App\Enum\ContactSourceEnum;
use App\Enum\ContactTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyContactRequest extends FormRequest
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
            //'phone' => 'required|max:20',
             'phone' => 'nullable|max:20',
            'website' => 'nullable|url|max:255',
            'billing_street' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_code' => 'nullable|numeric',
            'bid_due_date' =>'nullable|date_format:Y-m-d',
            'from_modal' => 'sometimes|boolean',
            'clients' => 'sometimes|array',
            'clients.*.source' => [
                'nullable',
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
                    ContactSourceEnum::NEW_ORDER->value,
                    ContactSourceEnum::GOOGLE_ADS->value,
                    ContactSourceEnum::SAME_AS_ORDER->value,
                    ContactSourceEnum::DIRECT_CALL->value,
                ),
            ],
            'clients.*.phone' => [
                'required',
                'max:20',
                'distinct',
                Rule::unique('clients', 'phone'),
            ],
            'clients.*.refer_name' => 'nullable|string|max:255',
            'clients.*.refer_phone' => 'nullable|string|max:50',
            'clients.*.refer_email' => 'nullable|email|max:255',
            'clients.*.referral_id' => 'nullable|integer|exists:referrals,id',
            'clients.*.referrer_client_id' => 'nullable|integer|exists:clients,id',
            'clients.*.referrer_user_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
