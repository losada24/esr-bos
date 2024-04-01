<?php

namespace App\Http\Requests;

use App\Enum\ExternalProductEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExternalProductsRequest extends FormRequest
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
            'id' => 'required|exists:external_products_configurations,id',
            'external_product' => [
              'required',
              'max:255',
              Rule::in([
                ExternalProductEnum::$MULLION,
                ExternalProductEnum::$CASEMENT
              ])
            ],
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'extras' => 'nullable|JSON',
            'notes' => 'nullable|string',
        ];
    }
}
