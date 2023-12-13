<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Enum\HorizontalRollerConfigEnum;
use App\Enum\HorizontalRollerHandleEnum;

class UpdateHorizontalRollerRequest extends FormRequest
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
          'id' => 'required|exists:products,id',
          'mark' => 'required|string|max:255',
          'width' => 'required|numeric',
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
          'screen' => 'required|boolean',
          'config' => [
            'required',
            Rule::in(array_values(HorizontalRollerConfigEnum::$CONFIG))
          ],
          'handle' => [
            'required',
            Rule::in(array_values(HorizontalRollerHandleEnum::$HANDLE))
          ],
        ];
    }
}
