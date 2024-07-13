<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstallationTeamRequest extends FormRequest
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
            'id' => 'required|exists:installation_teams,id',
            'user_id' => 'required|exists:users,id',
            'type_of_housings' => 'required|array',
            'number_of_member' => 'required|integer|min:1',
            'worker_compensation_expiration_date' => 'required|date_format:Y-m-d',
            'liability_expiration_date' => 'required|date_format:Y-m-d',
            'worker_compensation_attach' => 'nullable|file|mimes:jpeg,png,jpg,pdf,docx,doc,xlsx|max:512',
            'liability_expiration_attach' => 'nullable|file|mimes:jpeg,png,jpg,pdf,docx,doc,xlsx|max:512',
        ];
    }
}
