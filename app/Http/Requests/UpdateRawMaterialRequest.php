<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enum\UnitOfMeasurement;

class UpdateRawMaterialRequest extends FormRequest
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
          'id' => 'required|exists:raw_materials,id',
          'name' => 'required|string|max:255',
          'qty' => 'required|numeric|min:0|digits_between:1,10',
          'unit_of_measurement' => [
            'required',
            Rule::in(array_values(UnitOfMeasurement::$UNIT_OF_MEASUREMENT))
          ],
          'cost_per_unit' => 'required|numeric|min:0',
          'notes' => 'nullable|string|max:255',
          'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:512'
        ];
    }
}
