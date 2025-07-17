<?php

namespace App\Http\Requests;

use App\Enum\ContactSourceEnum;
use App\Enum\ContactTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enum\States;

class UpdateClientRequest extends FormRequest
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
            'id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|max:20',
            'address' => 'nullable|string|max:500',
            'vip_clients' => 'boolean',
            'vip_notes' => 'nullable|string|max:1000',
            'contact_type' => [
              'required',
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
                ContactSourceEnum::META->value,
                ContactSourceEnum::DESTINO_TOLK->value,
                ContactSourceEnum::RESOURCE_MAGAZINE->value,
                ContactSourceEnum::BANNER_PUBLICITARIO->value,
                ContactSourceEnum::EXTERNAL_REFERAL->value,
                ContactSourceEnum::INTERNAL_REFERAL->value,
                ContactSourceEnum::GOOGLE_MY_BUSINESS->value,
                ContactSourceEnum::PICHY_BOYS->value,
              )
            ],
            'other_phone' => 'nullable|string|max:20',
            'secondary_email' => 'nullable|email|max:255',
            'source' => 'nullable|string|max:255',
        ];
    }
}
