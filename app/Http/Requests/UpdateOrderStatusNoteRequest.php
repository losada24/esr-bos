<?php

namespace App\Http\Requests;

use App\Rules\ValidateStatusNoteOwner;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusNoteRequest extends FormRequest
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
            'id' => [
              'required',
              'exists:order_status,id',
              new ValidateStatusNoteOwner(),
            ],
            'notes' => 'required|string|max:500',
        ];
    }
}
