<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use Illuminate\Validation\Rule;

class StoreSingleHuntRequest extends FormRequest
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
            'width' => 'required|numeric', // TODO: Manage width and height limits
            'height' => 'required|numeric',
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
            'low_e' => 'required|string|max:255',
            'privacy' => 'required|string|max:255',
            'screen' => 'required|boolean'
        ];
    }
}
