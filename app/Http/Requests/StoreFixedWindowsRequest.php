<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use Illuminate\Validation\Rule;
use App\Enum\GlassTypeEnum;
use App\Enum\ProductSystemEnum;
use App\Rules\ValidateMuntingHorizontalLines;
use App\Rules\ValidateMuntingVerticalLines;
use App\Rules\ValidateMuntinStyle;

class StoreFixedWindowsRequest extends FormRequest
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
            'mark' => 'required|string|max:255',
            'width' => 'required|numeric|min:12|max:74',
            'height' => 'required|numeric|min:12|max:120',
            'qty' => 'required|numeric|min:1',
            'markup' => 'required|numeric|min:0',
            'frame_color' => [
              'required',
              Rule::in(array_values(FrameColorEnum::$FRAME_COLOR))
            ],
            'glass_color' => [
              'required',
              Rule::in(array_values(GlassColorEnum::$GLASS_COLOR))
            ],
            'order_id' => 'required|exists:orders,id',
            'glass_type' => 'required|string|max:255',
            'low_e' => [
              'string',
              'nullable',
              'max:255',
              Rule::when(
                fn($input) => $input->order_glass_type != GlassTypeEnum::$GLASS_TYPE['RUSH']
                , ['required']
              ),
            ],
            'privacy' => [
              'string',
              'nullable',
              'max:255',
              Rule::when(
                fn($input) => $input->order_glass_type != GlassTypeEnum::$GLASS_TYPE['RUSH']
                , ['required']
              ),
            ],
            'muntin_panels' => 'boolean',
            'panel_a' => [
              'boolean',
              Rule::when(
                fn($input) => $input->muntin_panels
                , ['required', 'accepted']
              ),
            ],
            'muntin_pattern' => [
              'string',
              'nullable',
              'max:255',
              Rule::when(
                fn($input) => $input->muntin_panels
                , ['required']
              ),
            ],
            'muntin_interior_style' => [
              'string',
              'nullable',
              'max:255',
              Rule::when(
                fn($input) => $input->muntin_panels
                , [new ValidateMuntinStyle]
              )
            ],
            'muntin_exterior_style' => [
              'string',
              'nullable',
              'max:255'
            ],
            'horizontal_lines' => [
              'numeric',
              'nullable',
              Rule::when(
                fn($input) => $input->muntin_panels
                , [new ValidateMuntingHorizontalLines(ProductSystemEnum::$FIXED_WINDOWS)]
              ),
            ],
            'vertical_lines' => [
              'numeric',
              'nullable',
              Rule::when(
                fn($input) => $input->muntin_panels
                , [new ValidateMuntingVerticalLines(ProductSystemEnum::$FIXED_WINDOWS)]
              ),
            ],
        ];
    }
}
