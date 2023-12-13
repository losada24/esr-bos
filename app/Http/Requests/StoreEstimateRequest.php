<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Enum\GlassTypeEnum;
use Illuminate\Validation\Rule;

class StoreEstimateRequest extends FormRequest
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
            'project_name' => 'string|max:255',
            'client_id' => 'required|exists:clients,id', // TODO: ONLY SHOW CLIENTS THAT BELONG TO THE USER
            'frame_color' => [
              'required',
              Rule::in(array_values(FrameColorEnum::$FRAME_COLOR))
            ],
            'glass_color' => [
              'required',
              Rule::in(array_values(GlassColorEnum::$GLASS_COLOR))
            ],
            'markup' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0',
            'installation' => 'required|numeric|min:0',
            'permit' => 'required|numeric|min:0',
            'other' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
            'external_purchase_id' => 'nullable|string|max:255',
            'glass_type' => [
              'required',
              'max:255',
              Rule::in(array_values(GlassTypeEnum::$GLASS_TYPE))
            ],
        ];
    }
}
