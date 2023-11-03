<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enum\UnitOfMeasurement;
use Illuminate\Validation\Rule;

class StoreRawMaterialRequest extends FormRequest
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
            'qty' => 'required|numeric|min:0',
            'unit_of_measurement' => [
              'required',
              Rule::in(array_values(UnitOfMeasurement::$UNIT_OF_MEASUREMENT))
            ],
            'cost_per_unit' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
            'featured_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:512',
        ];
    }
}
