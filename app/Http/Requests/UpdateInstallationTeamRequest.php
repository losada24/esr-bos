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
            'user_id' => [
              'required',
              'exists:users,id',
              'unique:installation_teams,user_id,' . $this->id,
            ],
            'number_of_member' => 'required|integer|min:1',
            'worker_compensation_expiration_date' => 'required|date_format:Y-m-d',
            'liability_expiration_date' => 'required|date_format:Y-m-d',
            'worker_compensation_attach' => 'nullable|file|mimes:jpeg,png,jpg,pdf,docx,doc,xlsx|max:10240',
            'liability_expiration_attach' => 'nullable|file|mimes:jpeg,png,jpg,pdf,docx,doc,xlsx|max:10240',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'type_of_housings' => 'required|array',
            'type_of_housings.*' => 'exists:types_of_housing,id',
            'travel_costs' => 'required|array',
            'travel_costs.*' => 'exists:travel_costs,id',
        ];
    }
}
