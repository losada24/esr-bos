<?php

namespace App\Http\Requests;

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
            'email' => 'required|email',
            'phone' => 'required|max:20',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => [
              'required',
              'string',
              'max:100',
              Rule::in(array_values(States::$USA_STATES))
            ],
            'zip' => 'required|numeric|max_digits:5|min_digits:5',
            'company_id' => 'nullable|numeric'
        ];
    }
}
