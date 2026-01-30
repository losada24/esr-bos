<?php

namespace App\Http\Requests;

use App\Enum\ContactSourceEnum;
use App\Enum\ContactTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enum\States;

class UpdateCompanyContactRequest extends FormRequest
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
        $clients = $this->input('clients', []);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|max:20',
            'website' => 'nullable|url|max:255',
            'billing_street' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_code' => 'nullable|numeric',
            'bid_due_date' =>'nullable|date_format:Y-m-d',
            'clients' => 'sometimes|array',
        ];

        foreach ($clients as $index => $client) {
            $clientId = isset($client['id']) ? (int) $client['id'] : null;
            $uniqueRule = Rule::unique('clients', 'phone');
            if (!empty($clientId)) {
                $uniqueRule->ignore($clientId);
            }
            $rules["clients.$index.phone"] = [
                'required',
                'max:20',
                'distinct',
                $uniqueRule,
            ];
        }

        return $rules;
    }
}
