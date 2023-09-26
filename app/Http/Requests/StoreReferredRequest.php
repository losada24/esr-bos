<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferredRequest extends FormRequest
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
            'email' => 'required|email|unique:referrals,email',
            'notes' => 'nullable|string|min:8|max:500',
            'phone' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
            'captcha_token' => [
                'required',
                'string',
                new \App\Rules\Recaptcha(),
            ],
        ];
    }
}
