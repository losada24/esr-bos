<?php

namespace App\Http\Requests;

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
            'phone' => 'required|max:20',
            'address' => 'nullable|string|max:500',
            'appointment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'vip_clients' => 'boolean',
            'vip_notes' => 'nullable|string|max:1000',
        ];
    }
}
