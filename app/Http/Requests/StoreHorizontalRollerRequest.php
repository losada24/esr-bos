<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Enum\GlassTypeEnum;
use App\Enum\HorizontalRollerConfigEnum;
use App\Enum\HorizontalRollerHandleEnum;
use App\Enum\ProductSystemEnum;
use App\Rules\ValidateMuntingHorizontalLines;
use App\Rules\ValidateMuntingVerticalLines;
use App\Rules\ValidateMuntinPanels;
use App\Rules\ValidateMuntinStyle;
use App\Rules\ValidateRegularGlassTypeWithLowE;
use Illuminate\Validation\Rule;

class StoreHorizontalRollerRequest extends FormRequest
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
    { // TODO: Manage glass type, low e and privacy from enums
        return [
            'mark' => 'required|string|max:255',
            'width' => 'required|numeric|min:20|max:111',
            'height' => 'required|numeric|min:19|max:74',
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
              Rule::when(
                fn($input) => $input->order_glass_type == GlassTypeEnum::$REGULAR_GLASS_TYPE
                , [new ValidateRegularGlassTypeWithLowE(ProductSystemEnum::$HORIZONTAL_ROLLER)]
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
            'screen' => 'required|boolean',
            'anchors' => 'boolean',
            'config' => [
              'required',
              Rule::in(array_values(HorizontalRollerConfigEnum::$CONFIG))
            ],
            'handle' => [
              'required',
              Rule::in(array_values(HorizontalRollerHandleEnum::$HANDLE))
            ],
            'muntin_panels' => 'boolean',
            'panel_a' => [
              'boolean',
              Rule::when(
                fn($input) => $input->muntin_panels
                , [new ValidateMuntinPanels]
              ),
            ],
            'panel_b' => [
              'boolean'
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
                , [new ValidateMuntingHorizontalLines(ProductSystemEnum::$HORIZONTAL_ROLLER)]
              ),
            ],
            'vertical_lines' => [
              'numeric',
              'nullable',
              Rule::when(
                fn($input) => $input->muntin_panels
                , [new ValidateMuntingVerticalLines(ProductSystemEnum::$HORIZONTAL_ROLLER)]
              ),
            ],
        ];
    }
}
