<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enum\ReferredStatusEnum;

class UpdateReferredRequest extends FormRequest
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
          'id' => 'required|exists:referrals,id',
          'name' => 'required|string|max:255',
          'email' => [
            'required',
            'email',
            Rule::unique('App\Models\Referred')->ignore($this->id),
          ],
          'phone' => 'nullable|string|max:255',
          'notes' => 'nullable|string|max:500',
          'status' => [
            'required',
            Rule::in([ReferredStatusEnum::$NEW, ReferredStatusEnum::$PROCESSING, ReferredStatusEnum::$SIGNED_THE_CONTRACT, ReferredStatusEnum::$REJECTED]),
          ],
          'status_notes' => 'nullable|string|max:500'
        ];
    }
}
