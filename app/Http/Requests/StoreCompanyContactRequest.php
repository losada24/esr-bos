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

    protected function prepareForValidation(): void
    {
        $clients = collect($this->input('clients', []))
            ->map(function ($client) {
                if (!is_array($client)) {
                    return $client;
                }

                if (empty($client['id'])) {
                    $client['id'] = null;
                }

                return $client;
            })
            ->all();

        $this->merge([
            'clients' => $clients,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $clients = $this->input('clients', []);
        $sources = array_map(
            static fn (ContactSourceEnum $source): string => $source->value,
            ContactSourceEnum::cases()
        );

        $rules = [
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
            'clients.*.id' => 'nullable|integer|exists:clients,id',
            'clients.*.source' => [
                'nullable',
                'string',
                Rule::in($sources),
            ],
            'clients.*.refer_name' => 'nullable|string|max:255',
            'clients.*.refer_phone' => 'nullable|string|max:50',
            'clients.*.refer_email' => 'nullable|email|max:255',
            'clients.*.referral_id' => 'nullable|integer|exists:referrals,id',
            'clients.*.referrer_client_id' => 'nullable|integer|exists:clients,id',
            'clients.*.referrer_user_id' => 'nullable|integer|exists:users,id',
        ];

        foreach ($clients as $index => $client) {
            if (!empty($client['id'])) {
                $rules["clients.$index.phone"] = [
                    'nullable',
                    'max:20',
                ];

                continue;
            }

            $rules["clients.$index.phone"] = [
                'required',
                'max:20',
                'distinct',
                Rule::unique('clients', 'phone'),
            ];
        }

        return $rules;
    }
}
