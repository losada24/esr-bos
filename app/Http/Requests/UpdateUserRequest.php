<?php

namespace App\Http\Requests;

use App\Enum\StatusUserEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
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
          'id' => 'required|exists:users,id',
          'name' => 'required|string|max:255',
          'phone' => 'nullable|max:20',
          'email' => [
            'required',
            'email',
            Rule::unique('App\Models\User')->ignore($this->id),
          ],
          'password' => 'nullable|string|min:8|confirmed',
          //'role' => 'required|exists:roles,id',
          'role' => 'required|array', // Debe ser un array
          'role.*' => 'exists:roles,id', // Cada rol debe existir en la tabla roles
          'delegated_owner_ids' => 'nullable|array',
          'delegated_owner_ids.*' => ['integer', 'exists:users,id', 'different:id'],
          'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:512',
          'status' => ['required', new Enum(StatusUserEnum::class)],
        ];
    }
}
