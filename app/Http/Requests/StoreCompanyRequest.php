<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enum\States;

class StoreCompanyRequest extends FormRequest
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
            'phone_number' => 'required|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => [
              'required',
              'string',
              'max:100',
              Rule::in(array_values(States::$USA_STATES))
            ],
            'zip' => 'required|numeric|max_digits:5|min_digits:5',
            'featured_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:512',
            'markup' => 'nullable|integer|min:0|max:100',
            'promotion' => 'nullable|numeric|min:0|max:100',
            'allow_credit_payment' => 'nullable|boolean',
        ];
    }
}
